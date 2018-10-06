<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Monolog\Logger;
use Yansongda\Pay\Pay;
use Carbon\Carbon;
use Elasticsearch\ClientBuilder as ESClientBuilder;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //当laravel渲染products.index和products.show模板时，就会使用CategoryTreeComposer这个来注入类目树变量
        //同时laravel还支持通配符，例如products.*即代表当渲染products目录下的模版时都执这个ViewComposer
        \View::composer(['products.index', 'products.show'], \App\Http\ViewComposers\CategoryTreeComposer::class);
        Carbon::setLocale('zh');
        if(app()->environment('local')){
            \DB::listen(function($query){
                \Log::info(Str::replaceArray('?', $query->bindings, $query->sql));
            });
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //往服务容器中注入一个名为alipay的单例对象
        $this->app->singleton('alipay', function(){
            $config = config('pay.alipay');
            $config['notify_url'] = ngrok_url('payment.alipay.notify');
            $config['return_url'] = route('payment.alipay.return');
            //判断当前项目运行环境是否为线上环境
            if(app()->environment() !== 'production'){
                $config['mode'] = 'dev';
                $config['log']['level'] = Logger::DEBUG;
            }else{
                $config['log']['level'] = Logger::WARNING;
            }

            //调用Yansongda\Pay来创建一个支付宝对象
            return Pay::alipay($config);
        });

        $this->app->singleton('wechat_pay', function(){
            $config = config('pay.wechat');
            $config['notify_url'] = ngrok_url('payment.wechat.notify');
            if(app()->environment() !== 'production'){
                $config['log']['level'] = Logger::DEBUG;
            }else{
                $config['log']['level'] = Logger::WARNING;
            }

            return Pay::wechat($config);
        });

        //注册一个名为es的单例
        $this->app->singleton('es', function(){
            //从配置文件读取Elasticsearch服务器列表
            $builder = ESClientBuilder::create()->setHosts(config('database.elasticsearch.hosts'));
            if(app()->environment() === 'local'){
                //配置日志，Elasticsearch的请求和返回数据将打印到日志文件中，方便我们调试
                $builder->setLogger(app('log')->getMonolog());
            }

            return $builder->build();
        });
    }
}
