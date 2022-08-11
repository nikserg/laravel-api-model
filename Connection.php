<?php

namespace nikserg\LaravelApiModel;


use Closure;
use GuzzleHttp\Client;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Spatie\LaravelIgnition\Exceptions\InvalidConfig;

/**
 * Base settings for connection to host
 */
class Connection extends \Illuminate\Database\Connection
{
    protected Client $client;
    protected static string $jwt;

    public static function setJwt(string $jwt)
    {
        self::$jwt = $jwt;
    }

    public function __construct(
        protected string $baseUri,
        protected bool $verify,
        protected ?string $configuratorClass
    ) {
    }

    /**
     * Client for http-requests
     *
     *
     * @return \GuzzleHttp\Client
     * @throws \Exception
     */
    public function getClient(): Client
    {
        if (!isset($this->client)) {

            $config = [
                'base_uri' => $this->baseUri,
                'verify'   => $this->verify,
            ];
            if ($this->configuratorClass) {
                $config = ($this->configuratorClass)::modifyConfig($config);
            }
            $this->client = new Client($config);
        }

        return $this->client;
    }

    public function query(): Builder
    {
        return new ApiModelBaseQueryBuilder($this);
    }
}
