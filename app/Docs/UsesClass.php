<?php

namespace App\Docs;

use Attribute;

#[Attribute]
class UsesClass
{
    public function __construct(
        public readonly string $class,
    ) {
    }
}
