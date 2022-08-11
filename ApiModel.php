<?php

namespace nikserg\LaravelApiModel;

use Illuminate\Database\Eloquent\Model;

/**
 * Extend this model to use Eloquent with API-requests
 */
class ApiModel extends Model
{
    public function newEloquentBuilder($query)
    {
        return new ApiModelEloquentBuilder($query);
    }

}
