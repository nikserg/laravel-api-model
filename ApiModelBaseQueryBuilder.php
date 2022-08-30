<?php

namespace nikserg\LaravelApiModel;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Utils;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Request;
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

            $response = $this->connection->getClient()->request('GET', $this->customUrl);
            $body     = $response->getBody()->getContents();

            try {
                $decoded = Utils::jsonDecode($body, true);
            } catch (InvalidArgumentException) {
                throw new InvalidArgumentException('Not JSON answer: ' . $body);
            }

            $models = $this->getListOfModels($decoded);
        }

        return $models ?? $this->listOfModels;
    }

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
     * Получаем ответ с другого сервера,
     * если не пришла дата то выдаем обычный респонс
     *
     * @param $id
     * @return array|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getOne($id): ?array
    {
        try {
            $response = $this->connection->getClient()->request('GET',
            $this->customUrl . '/' . $id);
        } catch (ClientException $exception) {
            if ($exception->getCode() == 404) {
                return null;
            }
        }

        $body    = $response->getBody()->getContents();
        $decoded = Utils::jsonDecode($body, true);

        if (!array_key_exists('data', $decoded)) {
            throw new InvalidArgumentException('Missing a key data ' . $body);
        }

        return $decoded['data'];
    }

    /**
     * @param \Illuminate\Database\ConnectionInterface $connection Connection to remote API
     */
    public function __construct(ConnectionInterface $connection, ?string $customUrl = null)
    {
        $this->connection = $connection;

        $this->customUrl = $customUrl;
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

        if (!empty($this->orders) || !empty($search)) {

            $page = $this->offset / $this->limit;

            $models = $this->getOrderBy([
                'column'    => $this->orders[0]['column'],
                'direction' => $this->orders[0]['direction'],
                'search'    => count($search) ? json_encode($search) : [],
                'per_page'  => $this->limit,
                'page'      => $page,
            ])->models;
        }

        return collect($models);
    }

    /**
     * Запрос с сортировкой и пагинацией
     *
     * В параметрах приходит массив с необходимой сортировкой
     * значением поиска и пагинация
     */
    public function getOrderBy(array $order): ?ListOfModels
    {
        $response = $this->connection->getClient()->request('GET', $this->customUrl, ['query' => [
            'column'    => $order['column'],
            'direction' => $order['direction'],
            'search'    => $order['search'],
            'per_page'  => $order['per_page'],
            'page'      => $order['page'] + 1,
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

    public function getCountForPagination($columns = ['*']): int
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
        $response = $this->connection->getClient()->request('POST', $this->customUrl, [
            'form_params' => $attributes,
        ]);

        $body    = $response->getBody()->getContents();
        $decoded = Utils::jsonDecode($body, true);

        if (!array_key_exists('data', $decoded)) {
            throw new InvalidArgumentException('Missing a key data ' . $body);
        }

        return $decoded['data'];
    }
}
