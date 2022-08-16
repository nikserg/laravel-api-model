<?php

namespace nikserg\LaravelApiModel;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Utils;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use nikserg\LaravelApiModel\Exception\NotImplemented;
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
            try {
                $decoded = Utils::jsonDecode($body, true);
            } catch (InvalidArgumentException) {
                throw new InvalidArgumentException('Not JSON answer: ' . $body);
            }

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
     * Get single record from server
     *
     *
     * @param $id
     * @return array|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getOne($id): ?array
    {
        try {
            $response = $this->connection->getClient()->request('GET',
                $this->from . '/' . $id);
        } catch (ClientException $exception) {
            if ($exception->getCode() == 404) {
                return null;
            }
        }
        $body = $response->getBody()->getContents();
        $decoded = Utils::jsonDecode($body, true);

        return $decoded['data'];
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
        if (!empty($this->wheres)) {
            foreach ($this->wheres as $where) {
                if ($where[0] === $this->defaultKeyName() && $where[1] === '=') {
                    //get by id
                    $record = $this->getOne($where[2]);

                    //if no record, return empty array
                    if (!$record) {
                        return collect([]);
                    }

                    //if there is record, return single-element array
                    return collect([$record]);
                } else {
                    throw new NotImplemented('Find clause ' . print_r($where, true) . ' is not supported');
                }
            }
        }

        $models = $this->list()->models;
        //dd(collect($models));
        return collect($models);
    }

    public function getCountForPagination($columns = ['*'])
    {
        return $this->list()->meta->total;
    }


    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if ($column != $this->defaultKeyName()) {
            throw new NotImplemented('Only find by `' . $this->defaultKeyName() . '` is available so far. You\'ve tried to find by ' . $column);
        }
        if (strtolower($boolean) != 'and') {
            throw new NotImplemented('OR where clauses are not available so far');
        }
        if ($operator != '=') {
            throw new NotImplemented('Only `=` operator is available so far');
        }
        $this->wheres = [
            [$column, $operator, $value, $boolean],
        ];

        return $this;
    }
}
