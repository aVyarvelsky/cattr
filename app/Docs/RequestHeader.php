<?php

namespace App\Docs;

use App\Docs\Schemas\SchemaInterface;
use App\Services\SwaggerService;
use Attribute;

#[Attribute]
class RequestHeader implements Dumpable
{
    public function __construct(
        private readonly string          $name,
        private readonly string          $description,
        private readonly SchemaInterface $schema,
        private readonly bool            $required = false,
        private readonly bool            $deprecated = false,
        private readonly bool            $shouldMask = false,
    ) {
    }
    final public function dump(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'required' => $this->required,
            'deprecated' => $this->deprecated,
            'example' => $this->shouldMask ? SwaggerService::MASKED_VALUE : null,
            'in' => 'header',
            'schema' => $this->schema->dump(),
            'x-masked' => $this->shouldMask,
        ];
    }
}
