<?php

namespace nikserg\LaravelApiModel;

use Illuminate\Database\Eloquent\Model;

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
        // dump(__FILE__.__LINE__);
        dump($query);
        return new ApiModelEloquentBuilder($query);
    }

    public function qualifyColumn($column)
    {
        return $column; //Otherwise here would be <table name>.id
    }
}
