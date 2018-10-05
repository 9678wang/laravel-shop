<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Installment;
use App\Exceptions\InvalidRequestException;
use App\Events\OrderPaid;
use Carbon\Carbon;
use Endroid\QrCode\QrCode;
use App\Models\InstallmentItem;
use App\Models\Order;

class InstallmentsController extends Controller
{
    public function index(Request $request)
    {
    	$installments = Installment::query()
    		->where('user_id', $request->user()->id)
    		->paginate(10);

    	return view('installments.index', ['installments' => $installments]);
    }

    public function show(Installment $installment)
    {
    	//取出当前分期付款的所有的还款计划，并按还款顺序排序
    	$items = $installment->items()->orderBy('sequence')->get();
    	
    	return view('installments.show', [
    		'installment' => $installment,
    		'items' => $items,
    		//下一个来完成还款的还款计划
    		'nextItem' => $items->where('paid_at', null)->first(),
    	]);
    }

    public function payByAlipay(Installment $installment)
    {
        if($installment->order->closed){
            throw new InvalidRequestException('对应的商品订单已被关闭');            
        }
        if($installment->status === Installment::STATUS_FINISHED){
            throw new InvalidRequestException('该分期订单已结清');
        }
        //获取当前分期付款最近的一个未支付的还款计划
        if(!$nextItem = $installment->items()->whereNull('paid_at')->orderBy('sequence')->first()){
            //如果没有未支付的还款，原则上不可能，因为如果分期已结清在上一个判断就退出了
            throw new InvalidRequestException('该分期订单已结清');
        }

        //调用支付宝的网页支付
        return app('alipay')->web([
            //支付订单号使用分期流水号+还款计划编号
            'out_trade_no' => $installment->no.'_'.$nextItem->sequence,
            'total_amount' => $nextItem->total,
            'subject' => '支付laravel shop的分期订单：'.$installment->no,
            //这里的notify_url和return_url可以覆盖掉在AppServiceProvider设置的回调地址
            'notify_url' => ngrok_url('installments.alipay.notify'),
            'return_url' => route('installments.alipay.return'),
        ]);
    }

    //支付宝前端回调
    public function alipayReturn()
    {
        try{
            app('alipay')->verify();
        }catch(\Exception $e){
            return view('pages.error', ['msg' => '数据不正确']);
        }

        return view('pages.success', ['msg' => '付款成功']);
    }

    //支付宝后端回调
    public function alipayNotify()
    {
        //校验支付宝回调参数是否正确
        $data = app('alipay')->verify();
        if($this->paid($data->out_trade_no, 'alipay', $data->trade_no)){
            return app('alipay')->success();
        }

        return 'fail';
    }

    public function payByWechat(Installment $installment)
    {
        if($installment->order->closed){
            throw new InvalidRequestException('对应的商品订单已被关闭');
        }
        if($installment->status === Installment::STATUS_FINISHED){
            throw new InvalidRequestException('该分期订单已结清');
        }
        if(!$nextItem = $installment->items()->whereNull(paid_at)->orderBy('sequence')->first()){
            throw new InvalidRequestException('该分期订单已结清');
        }
        $wechatOrder = app('wechat_pay')->scan([
            'out_trade_no' => $installment->no.'_'.$nextItem->sequence,
            'total_fee' => $nextItem->total * 100,
            'body' => '支付laravel shop的分期订单：'.$installment->no,
            'notify_url' => ngrok_url('installments.wechat.notify'),
        ]);
        //把要转换的字符串作为QrCode的构造函数参数
        $qrCode = new QrCode($wechatOrder->code_url);

        //将生成的二维码图片数据以字符串形式输出，并带上相应的响应类型
        return response($qrCode->writeString(), 200, ['Content-Type' => $qrCode->getContentType()]);
    }

    public function wechatNotify()
    {
        $data = app('wechat_pay')->verify();
        if($this->paid($data->out_trade_no, 'wechat', $data->transation_id)){
            return app('wechat_pay')->success();
        }

        return 'fail';
    }

    protected function paid($outTradeNo, $paymentMethod, $paymentNo)
    {
        //通过支付订单号来还原出这笔还款是哪个分期付款的哪个还款计划
        list($no, $sequence) = explode('_', $outTradeNo);
        //根据分期流水号查询对应的分期记录，原则上不会找不到，这里的判断指数增强代码的健壮性
        if(!$installment = Installment::where('no', $no)->first()){
            return false;
        }
        //根据还款计划编号查询对应的还款计划，原则上不会找不到，这里的判断指数增强代码健壮性
        if(!$item = $installment->items()->where('sequence', $sequence)->first()){
            return false;
        }
        //如果这个还款计划的支付状态是已支付，则告知支付宝此订单已完成，并不再执行后续逻辑
        if($item->paid_at){
            return true;
        }
        //更新对应的还款计划
        $item->update([
            'paid_at' => Carbon::now(),//支付时间
            'payment_method' => $paymentMethod,//支付方式
            'payment_no' => $paymentNo,//订单号
        ]);
        //如果这是第一笔还款
        if($item->sequence === 0){
            //将分期付款的状态改为还款中
            $installment->update(['status' => Installment::STATUS_REPAYING]);
            //将分期付款对应的商品订单状态改为已支付
            $installment->order->update([
                'paid_at' => Carbon::now(),
                'payment_method' => 'installment',//支付方式为分期付款
                'payment_no' => $no,//支付订单号为分期付款的流水号
            ]);
            //触发商品订单已支付的事件
            event(new OrderPaid($installment->order));
        }
        //如果这是最后一笔还款
        if($item->sequence === $installment->count - 1){
            //将分期付款状态改为已结清
            $installment->update(['status' => Installment::STATUS_FINISHED]);
        }

        return true;
    }

    public function wechatRefundNotify(Request $request)
    {
        //给微信的失败响应
        $failXml = '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[FAIL]]></return_msg></xml>';
        //校验微信回调参数
        $data = app('wechat_pay')->verify(null, true);
        //根据单号拆解出对应的商品退款单号及对应的还款计划序号
        list($no, $sequence) = explode('_', $data['out_refund_no']);

        $item = InstallmentItem::query()
            ->whereHas('installment', function($query) use($no){
                $query->whereHas('order', function($query) use($no){
                    $query->where('refund_no', $no);//根据订单表的退款流水号找到对应还款计划
                });
            })
            ->where('sequence', $sequence)
            ->first();
        if(!$item){
            return $failXml;
        }
        //如果退款成功
        if($data['refund_status'] === 'SUCESS'){
            $item->update([
                'refund_status' => InstallmentItem::REFUND_STATUS_SUCCESS,
            ]);
            $item->installment->refreshRefundStatus();
        }else{
            $item->update([
                'refund_status' => InstallmentItem::REFUND_STATUS_FAILED,
            ]);
        }

        return app('wechat_pay')->success();
    }
}
