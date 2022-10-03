<?php

namespace nikserg\LaravelApiModel;

/**
 * Can change initialization config of Guzzle clients, to add, for example, authorization header
 */
interface GuzzleConfigurator
{
    public static function modifyConfig(array $config): array;
}
