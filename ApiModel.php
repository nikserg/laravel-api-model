<?php

namespace nikserg\LaravelApiModel;

use Illuminate\Database\Eloquent\Model;

class ApiModel extends Model
{

    protected function newBaseQueryBuilder()
    {
        return new ApiModelQueryBuilder();
    }
}
