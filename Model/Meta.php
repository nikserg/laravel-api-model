<?php

namespace nikserg\LaravelApiModel\Model;

/**
 * Metadata of page of models
 */
class Meta
{
    public function __construct(
        public int $current_page,
        public ?int $from,
        public int $last_page,
        public array $links,
        public string $path,
        public int $per_page,
        public ?int $to,
        public int $total,
    ) {
    }
}
