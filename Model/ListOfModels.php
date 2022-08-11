<?php

namespace nikserg\LaravelApiModel\Model;

/**
 * List of on page, including metadata
 */
class ListOfModels
{
    public function __construct(
        public Links $links,
        public Meta $meta,
        public array $models,
    ) {
    }
}
