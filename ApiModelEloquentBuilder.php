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

    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        // dd($this->query);
        $this->wheres = [
            [$column, $operator, $value, $boolean],
        ];
        dump('query where builder');

        return $this;
        // dump('first where');
        // return $this->query->update(['id' => 'EGAIS'], $this->getModel());
    }

    public function create(array $attributes = [])
    {
        $model = $this->getModel();

        return $this->query->create($attributes, $model);
    }

    public function update(array $attributes = [])
    {
        dump('updateEloquent');
        dd($this);
        // dd($this->query->connection);
        // dd((new ApiModelBaseQueryBuilder($this->query->connection))->update([]));
        // dd($this->query);
        dd('end');
        // dump(123);
        dd();
        $model = $this->getModel();

        return $this->query->update($attributes, $model);
    }
}
