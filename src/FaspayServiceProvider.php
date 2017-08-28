<?php

namespace Krisnasw\Faspay;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\ServiceProvider;

class FaspayServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        $this->publishes([
                __DIR__.'../config/faspay.php' => config_path('faspay.php'),
            ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->app->bind('faspay', function ($app) {
            $userid = $app['config']['faspay.user_id'];
            $password = $app['config']['faspay.password'];
            $merchantCode = $app['config']['faspay.merchant_code'];
            $merchantName = $app['config']['faspay.merchant_name'];
            $faspay = new Faspay(
                $userid, $password, 
                $merchantCode, $merchantName, [
                    'production' => $app->environment('production'),
                    'expiration_hours' => $app['config']['faspay.expiration_hours']
                ]
            );
            $faspay->setHttpClient(new HttpClient([
                'timeout' => 600.0,
                'verify' => false
            ]));
            
            return $faspay;
        });

        $this->app->bind('payment', function($app) {
            return new Payment();
        });
    }

    public function provides()
    {
        return ['faspay', 'payment'];
    }
}
