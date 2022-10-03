<?php

namespace nikserg\LaravelApiModel;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Utils;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
    /**
     * List of on page, including metadata
     *
     * @var ListOfModels
     */
    private ListOfModels $listOfModels;

    /**
     * Get list of models from remote server, including metadata.
     * Typical response from standard JSON Resource.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function list(): ListOfModels
    {
        if (!isset($this->listOfModels)) {
            $page = 0;
            if ($this->limit ?? null) {
                $page = $this->offset / $this->limit;
            }

            $search = [];
            if (!empty($this->wheres)) {
                foreach ($this->wheres as $where) {
                    if (array_key_exists('query', $where)) {
                        $search = $where['query']->wheres;
                    }
                }
            }

            try {
                $response = $this->connection->getClient()->request('GET', $this->customUrl, ['query' => [
                    'column'    => isset($this->orders[0]['column']) ? $this->orders[0]['column'] : null,
                    'direction' => isset($this->orders[0]['direction']) ? $this->orders[0]['direction'] : null,
                    'search'    => count($search) ? json_encode($search) : [],
                    'per_page'  => $this->limit,
                    'page'      => $page + 1,
                ]]);
                $body = $response->getBody()->getContents();
                $decoded = Utils::jsonDecode($body, true);
            } catch (InvalidArgumentException) {
                throw new InvalidArgumentException('Not JSON answer: ' . $body);
            }

            $models = $this->getListOfModels($decoded);
        }

        return $models ?? $this->listOfModels;
    }

    /**
     * Build list of models from response data
     *
     * @param array $response
     * @return ListOfModels
     */
    public function getListOfModels(array $response): ListOfModels
    {
        return $this->listOfModels = new ListOfModels(
            links:new Links(
                first:$response['links']['first'],
                last:$response['links']['last'],
                next:$response['links']['next'],
                prev:$response['links']['prev']
            ),
            meta:new Meta(
                current_page:$response['meta']['current_page'],
                from:$response['meta']['from'],
                last_page:$response['meta']['last_page'],
                links:$response['meta']['links'],
                path:$response['meta']['path'],
                per_page:$response['meta']['per_page'],
                to:$response['meta']['to'],
                total:$response['meta']['total']
            ),
            models:$response['data'],
        );
    }

    /**
     * Get single record from server
     *
     * Receive a response from another server,
     * if date not set, then we issue default response
     *
     * @param $id
     * @return array|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getOne($id): ?array
    {
        try {
            $response = $this->connection->getClient()->request('GET', $this->customUrl . '/' . $id);
            $body = $response->getBody()->getContents();
            $decoded = Utils::jsonDecode($body, true);
        } catch (InvalidArgumentException) {
            throw new InvalidArgumentException('Not JSON answer: ' . $body);
        }

        if (!array_key_exists('data', $decoded)) {
            throw new InvalidArgumentException('Missing a key data ' . $body);
        }

        return $decoded['data'];
    }

    /**
     * Get all records from server
     *
     * Receive a response from another server,
     * if date not set, then we issue default response
     *
     * @param $id
     * @return array|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getAll(): ?array
    {
        try {
            $response = $this->connection->getClient()->request('GET', $this->customUrl, ['query' => ['per_page' => 0]]);
            $body = $response->getBody()->getContents();
            $decoded = Utils::jsonDecode($body, true);
        } catch (InvalidArgumentException) {
            throw new InvalidArgumentException('Not JSON answer: ' . $body);
        }

        if (!array_key_exists('data', $decoded)) {
            throw new InvalidArgumentException('Missing a key data ' . $body);
        }

        return $decoded['data'];
    }

    /**
     * @param \Illuminate\Database\ConnectionInterface $connection Connection to remote API
     * @param string|null $customUrl Custom additional uri fragment
     */
    public function __construct(ConnectionInterface $connection, ?string $customUrl = null)
    {
        $this->connection = $connection;
        $this->customUrl = $customUrl;
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array|string  $columns
     * @return \Illuminate\Support\Collection
     */
    public function get($columns = ['*'])
    {
        if (!empty($this->wheres)) {
            foreach ($this->wheres as $where) {
                if ($where['column'] === $this->defaultKeyName() && $where['operator'] === '=') {
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

        //if limit not set, then return all records without pagination
        if ($this->limit ?? null) {
            $records = $this->list()->models;
        } else {
            $records = $this->getAll();
        }

        return collect($records);
    }

    /**
     * Get the count of the total records for the paginator.
     *
     * @param  array  $columns
     * @return int
     */
    public function getCountForPagination($columns = ['*'])
    {
        return $this->list()->meta->total;
    }

    /**
     * Add a basic where clause to the query.
     *
     * @param  \Closure|string|array  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @param  string  $boolean
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        $this->wheres[] = compact(
            'column', 'operator', 'value', 'boolean'
        );

        return $this;
    }

    /**
     * Save a new model and return data from remote server.
     *
     * @param  array  $attributes
     * @return array
     */
    public function create(array $attributes = [])
    {
        try {
            $response = $this->connection->getClient()->request('POST', $this->customUrl, [
                'form_params' => $attributes,
            ]);
            $body = $response->getBody()->getContents();
            $decoded = Utils::jsonDecode($body, true);
        } catch (InvalidArgumentException) {
            throw new InvalidArgumentException('Not JSON answer: ' . $body);
        }

        if (!array_key_exists('data', $decoded)) {
            throw new InvalidArgumentException('Missing a key data ' . $body);
        }

        return $decoded['data'];
    }
}
