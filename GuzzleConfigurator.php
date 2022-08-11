<?php

namespace nikserg\LaravelApiModel;

use CrmCoreClients\Auth\AuthClient;
use CrmCoreCommon\Permissions\Common;
use GuzzleHttp\Client;
use Illuminate\Container\Container;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Application;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

/**
 * Can change initialization config of Guzzle clients, to add, for example, authorization header
 */
interface GuzzleConfigurator
{
    public static function modifyConfig(array $config): array;
}
