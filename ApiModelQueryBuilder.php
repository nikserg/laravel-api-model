<?php

namespace nikserg\LaravelApiModel;


use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;

class ApiModelQueryBuilder extends Builder
{
    public function __construct()
    {
    }

    public function count($columns = '*') {
        die(__FILE__.__LINE__);
    }
}
