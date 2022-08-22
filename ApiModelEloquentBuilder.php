<?php

namespace nikserg\LaravelApiModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;
use nikserg\LaravelApiModel\Exception\NotImplemented;

/**
 * @property \nikserg\LaravelApiModel\ApiModelBaseQueryBuilder $query
 */
class ApiModelEloquentBuilder extends Builder
{
    public function count($columns = '*')
    {
        return $this->query->list()->meta->total;
    }

    public function min($column)
    {
        throw new NotImplemented();
    }

    public function max($column)
    {
        throw new NotImplemented();
    }

    public function create(array $attributes = [])
    {
        $model = $this->getModel();

        $response = $this->query->create($attributes);

        if ((int) $response['code'] < 200 || (int) $response['code'] > 204) {

            throw new InvalidArgumentException($response['data']);
        }

        return $model->fill($response['data']);
    }

    public function orWhere($column, $operator = null, $value = null)
    {
        return $this->query->where($column, $operator, $value, 'or');
    }
}
