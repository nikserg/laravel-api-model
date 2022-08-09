<?php

namespace nikserg\LaravelApiModel;

use CrmCoreClients\Auth\AuthClient;
use CrmCoreCommon\Permissions\Common;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

/**
 * Регистрируем новый драйвер БД
 */
class ApiModelServiceProvider extends ServiceProvider
{

    /**
     * Register the service provider.
     */
    public function register()
    {
        die('opk');
        // Add database driver.
        $this->app->resolving('db', function ($db) {
            $db->extend('api', function ($config, $name) {
                $config['name'] = $name;

                return new Connection($config);
            });
        });

        // Add connector for queue support.
        $this->app->resolving('queue', function ($queue) {
            $queue->addConnector('api', function () {
                return new ApiModelQueueConnector($this->app['db']);
            });
        });
    }
//    public function boot()
//    {
//        //Конфиг
//        $this->publishes([__DIR__ . '/config.php' => config_path('itcom-jwt.php')]);
//        $this->mergeConfigFrom(__DIR__ . '/config.php', 'itcom-jwt');
//
//        //Клиент для подключения к сервису авторизации
//        $this->app->singleton(AuthClient::class, function (Application $application)
//        {
//            return new (config('itcom-jwt.clientClass'))(
//                config('itcom-jwt.clientHost'),
//                config('itcom-jwt.jwtPublicKey')
//            );
//        });
//
//        //User Provider
//        Auth::provider('itcom-jwt', function (Application $app, array $config)
//        {
//            return new ItcomUserProvider($app->make(AuthClient::class));
//        });
//
//        //Gates
//        AuthHelper::registerAsGates(Common::class);
//    }
}
