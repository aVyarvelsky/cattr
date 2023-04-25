<?php

namespace App\Docs;

use Attribute;

#[Attribute]
class UsesMethod
{
    public function __construct(
        public readonly string $class,
        public readonly string $method,
    ) {
    }
}
