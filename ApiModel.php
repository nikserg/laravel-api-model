<?php

namespace nikserg\LaravelApiModel;

use GuzzleHttp\Utils;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\MySqlConnection;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Extend this model to use Eloquent with API-requests
 */
class ApiModel extends Model
{
    /**
     * While fetching from remote server, we need to set all attributes of model
     *
     * @var bool
     */
    protected static $unguarded = true;
    public $incrementing        = false;

    public function newEloquentBuilder($query)
    {
        return new ApiModelEloquentBuilder($query);
    }

    public function newBaseQueryBuilder(): Builder
    {
        return new ApiModelBaseQueryBuilder($this->getConnection());
    }

    public function qualifyColumn($column)
    {
        return $column; //Otherwise here would be <table name>.id
    }

    public function getDateFormat()
    {

    }

    public function findOrFail($id, $columns = ['*'])
    {
        return $this->getModel()->fill([$id]);
    }

    public function update(array $attributes = [], array $options = [])
    {
        $connection = $this->getConnection();

        try {

            $response = $connection->getClient()->request('PUT', $this->getTable() . '/' . $this->getAttributes()[0], [
                'form_params' => $attributes
            ]);

            $body = $response->getBody()->getContents();
            $decoded = Utils::jsonDecode($body, true);

            return $this->fill($decoded['data']);

        } catch (\Exception $e) {

            throw new InvalidArgumentException($response['data']);
        }
    }

    public function delete()
    {
        try {

            $this->getConnection()->getClient()->request('DELETE', $this->getTable() . '/' . $this->getAttributes()[0]);

            return true;

        } catch (\Exception $e) {

            throw new NotFoundHttpException($e->getMessage());
        }
    }
}
