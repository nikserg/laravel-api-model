<?php

namespace nikserg\LaravelApiModel;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Utils;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Processors\Processor;
use nikserg\LaravelApiModel\Exception\NotImplemented;
use nikserg\LaravelApiModel\Model\Links;
use nikserg\LaravelApiModel\Model\ListOfModels;
use nikserg\LaravelApiModel\Model\Meta;
use Illuminate\Database\Query\Grammars\Grammar;

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

            $response = $this->connection->getClient()->request('GET', $this->from);

            $body = $response->getBody()->getContents();

            try {

                $decoded = Utils::jsonDecode($body, true);

            } catch (InvalidArgumentException) {

                throw new InvalidArgumentException('Not JSON answer: ' . $body);
            }

            $models = $this->getListOfModels($decoded);
        }

        return $models ?? $this->listOfModels;
    }

    public function getListOfModels(array $response)
    {
        return $this->listOfModels = new ListOfModels(
            links: new Links(
                first: $response['links']['first'],
                last: $response['links']['last'],
                next: $response['links']['next'],
                prev: $response['links']['prev']
            ),
            meta: new Meta(
                current_page: $response['meta']['current_page'],
                from: $response['meta']['from'],
                last_page: $response['meta']['last_page'],
                links: $response['meta']['links'],
                path: $response['meta']['path'],
                per_page: $response['meta']['per_page'],
                to: $response['meta']['to'],
                total: $response['meta']['total']
            ),
            models: $response['data'],
        );
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
        $search = [];

        if (!empty($this->wheres)) {

            foreach ($this->wheres as $where) {

                if (array_key_exists('query', $where)) {

                    $search = $where['query']->wheres;

                } else if ($where['column'] === $this->defaultKeyName() && $where['operator'] === '=') {
                    //get by id
                    $record = $this->getOne($where['value']);

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

        if (!empty($this->orders) || array_key_exists('query', $where)) {

            $models = $this->getOrderBy((object) [
                'column'    => $this->orders[0]['column'],
                'direction' => $this->orders[0]['direction'],
                'search'    => count($search) ? json_encode($search) : []
            ])->models;
        }

        return collect($models);
    }

    public function getOrderBy(object $order)
    {
        $response = $this->connection->getClient()->request('GET', $this->from, ['query' => [
            'column'    => $order->column,
            'direction' => $order->direction,
            'search'    => $order->search,
        ]]);

        $body = $response->getBody()->getContents();

        try {

            $decoded = Utils::jsonDecode($body, true);

        } catch (InvalidArgumentException) {

            throw new InvalidArgumentException('Not JSON answer: ' . $body);
        }

        $models = $this->getListOfModels($decoded);

        return $models ?? $this->listOfModels;
    }

    public function getCountForPagination($columns = ['*'])
    {
        return $this->list()->meta->total;
    }


    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        $this->wheres[] = compact(
            'column', 'operator', 'value', 'boolean'
        );

        return $this;
    }

    public function create(array $attributes = [])
    {
        try {

            $response = $this->connection->getClient()->request('POST', $this->from, [
                'form_params' => $attributes
            ]);

            $body = $response->getBody()->getContents();
            $decoded = Utils::jsonDecode($body, true);

            return [
                'code' => $response->getStatusCode(),
                'data' => $decoded['data'],
            ];

        } catch (\Exception $e) {

            return [
                'code' => $e->getCode(),
                'data' => $e->getMessage()
            ];
        }
    }
}
