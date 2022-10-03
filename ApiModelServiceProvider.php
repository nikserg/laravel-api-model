<?php

namespace nikserg\LaravelApiModel;

use Illuminate\Database\DatabaseManager;
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
        $this->app->resolving('db', function (DatabaseManager $db) {
            $db->extend('api', function ($config, $name) {
                return new Connection($config['baseUri'], $config['verify'] ?? true, $config['configurator'] ?? null);
            });
        });
    }
}
