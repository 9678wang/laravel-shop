<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserAddress;
use App\Models\Order;
use App\Models\ProductSku;
use App\Exceptions\InvalidRequestException;
use App\Jobs\CloseOrder;
use Carbon\Carbon;
use App\Models\CouponCode;
use App\Exceptions\CouponCodeUnavailableException;
use App\Exceptions\InternalException;

class OrderService
{
	public function store(User $user, UserAddress $address, $remark, $items, CouponCode $coupon = null)
	{
		//如果传入了优惠券，则先检查是否可用
		if($coupon){
			//但此时我们还没有计算出订单总金额，因此先不校验
			$coupon->checkAvailable($user);
		}

		//开启一个数据库事务
		$order = \DB::transaction(function() use ($user, $address, $remark, $items, $coupon){
			//更新吃地址的最后使用时间
			$address->update(['last_used_at' => Carbon::now()]);
			//创建一个订单
			$order = new Order([
				'address' => [//将地址信息放入订单中
					'address' => $address->full_address,
					'zip' => $address->zip,
					'contact_name' => $address->contact_name,
					'contact_phone' => $address->contact_phone,
				],
				'remark' => $remark,
				'total_amount' => 0,
				'type' => Order::TYPE_NORMAL,
			]);
			//订单关联到当前用户
			$order->user()->associate($user);
			//写入数据库
			$order->save();

			$totalAmount = 0;
			//遍历用户提交的sku
			foreach($items as $data){
				$sku = ProductSku::find($data['sku_id']);
				//创建一个OrderItem并直接与订单关联
				$item = $order->items()->make([
					'amount' => $data['amount'],
					'price' => $sku->price,
				]);
				$item->product()->associate($sku->product_id);
				$item->productSku()->associate($sku);
				$item->save();
				$totalAmount += $sku->price * $data['amount'];
				if($sku->decreaseStock($data['amount']) <= 0){
					throw new InvalidRequestException('该商品库存不足');
				}
			}

			if($coupon){
				//总金额已经计算出来了，检查是否符合优惠券规则
				$coupon->checkAvailable($user, $totalAmount);
				//把订单金额修改为优惠后的金额
				$totalAmount = $coupon->getAdjustedPrice($totalAmount);
				//把订单与优惠券关联
				$order->CouponCode()->associate($coupon);
				//增加优惠券的用量，需判断返回值
				if($coupon->changeUsed() <= 0){
					throw new CouponCodeUnavailableException('该优惠券已被兑完');
				}
			}

			//更新订单总金额
			$order->update(['total_amount' => $totalAmount]);

			//将下单的商品从购物车中移除
			$skuIds = collect($items)->pluck('sku_id')->all();
			app(CartService::class)->remove($skuIds);

			return $order;
		});

		dispatch(new CloseOrder($order, config('app.order_ttl')));

		return $order;
	}

	//新建一个crowdfunding方法用于实现众筹商品下单逻辑
	public function crowdfunding(User $user, UserAddress $address, ProductSku $sku, $amount)
	{
		$order = \DB::transaction(function() use($amount, $sku, $user, $address){
			//更新地址最后使用时间
			$address->update(['last_used_at' => Carbon::now()]);
			//创建一个订单
			$order = new Order([
				'address' => [
					'address' => $address->full_address,
					'zip' => $address->zip,
					'contact_name' => $address->contact_name,
					'contact_phone' => $address->contact_phone,
				],
				'remark' => '',
				'total_amount' => $sku->price * $amount,
				'type' => Order::TYPE_CROWDFUNDING,
			]);
			//订单关联到当前用户
			$order->user()->associate($user);
			$order->save();
			//创建一个新的订单并与sku关联
			$item = $order->items()->make([
				'amount' => $amount,
				'price' => $sku->price,
			]);
			$item->product()->associate($sku->product_id);
			$item->productSku()->associate($sku);
			$item->save();
			//扣减对应sku库存
			if($sku->decreaseStock($amount) <= 0){
				throw new InvalidRequestException('该商品库存不足');				
			}

			return $order;
		});

		//众筹结束时间减去当前时间得到剩余秒数
		$crowdfundingTtl = $sku->product->crowdfunding->end_at->getTimestamp() - time();
		//剩余秒数与默认订单关闭时间取较小值作为订单关闭时间
		dispatch(new CloseOrder($order, min(config('app.order_ttl'), $crowdfundingTtl)));

		return $order;
	}

	public function refundOrder(Order $order)
	{
        //判断该订单的支付方式
        switch($order->payment_method){
            case 'wechat':
                $refundNo = Order::getAvailableRefundNo();
                app('wechat_pay')->refund([
                    'out_trade_no' => $order->no,
                    'total_fee' => $order->total_amount * 100,
                    'refund_fee' => $order->total_amount * 100,
                    'out_refund_no' => $refundNo,
                    //微信支付的退款结果不说实时返回的，而是通过退款回调来通过这，因此这里需要配上退款回调地址
                    'notify_url' => ngrok_url('payment.wechat.refund_notify'),
                ]);
                $order->update([
                    'refund_no' => $refundNo,
                    'refund_status' => Order::REFUND_STATUS_PROCESSING,
                ]);
                break;
            case 'alipay':
                $refundNo = Order::getAvailableRefundNo();
                //调用支付宝支付实例的refund方法
                $ret = app('alipay')->refund([
                    'out_trade_no' => $order->no,
                    'refund_amount' => $order->total_amount,
                    'out_request_no' => $refundNo,
                ]);
                //根据支付宝文档，如果返回值里有sub_code字段说明退款失败
                if($ret->sub_code){
                    $extra = $order->extra;
                    $extra['refund_faild_code'] = $ret->sub_code;
                    $order->update([
                        'refund_no' => $refundNo,
                        'refund_status' => Order::REFUND_STATUS_FAILED,
                        'extra' => $extra,
                    ]);
                }else{
                    $order->update([
                        'refund_no' => $refundNo,
                        'refund_status' => Order::REFUND_STATUS_SUCCESS,
                    ]);
                }
                break;
            default:
                //原则上不可能出现，这个只是为了代码健壮性
                throw new InternalException('未知订单支付方式：'.$order->payment_method);
                break;
        }
	}
}