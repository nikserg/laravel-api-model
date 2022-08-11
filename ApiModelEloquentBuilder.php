<?php

namespace nikserg\LaravelApiModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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
}
