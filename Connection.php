<?php

namespace nikserg\LaravelApiModel;


use Closure;
use Illuminate\Database\ConnectionInterface;

class Connection
{
    public function __construct($config)
    {
        print_r($config);exit;
    }

}
