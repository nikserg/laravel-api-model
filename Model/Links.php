<?php

namespace nikserg\LaravelApiModel\Model;

/**
 * Links of pagination
 */
class Links
{
    public function __construct(
        public string $first,
        public string $last,
        public ?string $next,
        public ?string $prev,
    ) {}
}
