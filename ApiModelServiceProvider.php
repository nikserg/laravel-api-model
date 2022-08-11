<?php

namespace nikserg\LaravelApiModel;

use CrmCoreClients\Auth\AuthClient;
use CrmCoreCommon\Permissions\Common;
use Illuminate\Container\Container;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Application;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

/**
 * Register new database driver
 *
 */
class ApiModelServiceProvider extends ServiceProvider
{
    public function boot()
    {
        ApiModel::setConnectionResolver($this->app['db']);
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        // Add database driver.
        $this->app->resolving('db', function (DatabaseManager $db)
        {
            $db->extend('api', function ($config, $name)
            {
                return new Connection($config['baseUri'], $config['verify'] ?? true, $config['configurator'] ?? null);
            });
        });

    }
}
