<?php

namespace nikserg\LaravelApiModel;

use GuzzleHttp\Utils;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use nikserg\LaravelApiModel\Model\Links;
use nikserg\LaravelApiModel\Model\ListOfModels;
use nikserg\LaravelApiModel\Model\Meta;

/**
 * @property \nikserg\LaravelApiModel\Connection $connection
 */
class ApiModelBaseQueryBuilder extends Builder
{

    private ListOfModels $listOfModels;

    /**
     * Get list of models from remote server, including metadata.
     * Typical response from standard JSON Resource.
     *
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function list(): ListOfModels
    {
        if (!isset($this->listOfModels)) {
            $response = $this->connection->getClient()->request('GET',
                $this->from);
            $body = $response->getBody()->getContents();
            $decoded = Utils::jsonDecode($body, true);

            $this->listOfModels = new ListOfModels(
                links: new Links(
                    first: $decoded['links']['first'],
                    last: $decoded['links']['last'],
                    next: $decoded['links']['next'],
                    prev: $decoded['links']['prev']
                ),
                meta: new Meta(
                    current_page: $decoded['meta']['current_page'],
                    from: $decoded['meta']['from'],
                    last_page: $decoded['meta']['last_page'],
                    links: $decoded['meta']['links'],
                    path: $decoded['meta']['path'],
                    per_page: $decoded['meta']['per_page'],
                    to: $decoded['meta']['to'],
                    total: $decoded['meta']['total']
                ),
                models: $decoded['data'],
            );
        }

        return $this->listOfModels;
    }

    /**
     * @param \Illuminate\Database\ConnectionInterface $connection Connection to remote API
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }


    public function get($columns = ['*'])
    {
        return collect($this->list()->models);
    }

    public function getCountForPagination($columns = ['*'])
    {
        return $this->list()->meta->total;
    }
}
