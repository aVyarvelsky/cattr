<?php

namespace App\Docs;

use Attribute;

#[Attribute]
class ResponseExampleFilter
{
    public function __construct(
        public readonly string $attributePath,
    ) {
    }
}
