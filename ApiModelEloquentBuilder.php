<?php

namespace nikserg\LaravelApiModel;
use Illuminate\Database\Eloquent\Builder;
use nikserg\LaravelApiModel\Exception\NotImplemented;

/**
 * @property \nikserg\LaravelApiModel\ApiModelBaseQueryBuilder $query
 */
class ApiModelEloquentBuilder extends Builder
{
    /**
     * Retrieve the "count" result of the query.
     *
     * @param  string  $columns
     * @return int
     */
    public function count($columns = '*')
    {
        return $this->query->list()->meta->total;
    }

    /**
     * Retrieve the minimum value of a given column.
     *
     * @param  string  $column
     * @return mixed
     */
    public function min($column)
    {
        throw new NotImplemented();
    }

    /**
     * Retrieve the maximum value of a given column.
     *
     * @param  string  $column
     * @return mixed
     */
    public function max($column)
    {
        throw new NotImplemented();
    }

    /**
     * Save a new model and return the instance.
     *
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model|$this
     */
    public function create(array $attributes = [])
    {
        $model = $this->getModel();
        $response = $this->query->create($attributes);

        return $model->fill($response);
    }

    /**
     * Add an "or where" clause to the query.
     *
     * @param  \Closure|string|array  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return $this
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        return $this->query->where($column, $operator, $value, 'or');
    }
}
