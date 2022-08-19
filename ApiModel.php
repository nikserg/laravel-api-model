<?php

namespace nikserg\LaravelApiModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

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
    public $incrementing = false;

    public function newEloquentBuilder($query)
    {
        return new ApiModelEloquentBuilder($query);
    }
    public function newBaseQueryBuilder(): Builder {
        return new ApiModelBaseQueryBuilder($this->getConnection());
    }

    public function qualifyColumn($column)
    {
        return $column; //Otherwise here would be <table name>.id
    }

    public function getDateFormat()
    {
        //TODO
    }

    public function findOrFail($id, $columns = ['*'])
    {
        return $this->select($columns)->from($this->getTable());
    }
}
