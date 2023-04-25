<?php

namespace App\Docs\Schemas;
use App\Docs\Schemas\Enums\JsonVarType;

readonly class JsonSchema implements SchemaInterface
{
    public function __construct(
        private JsonVarType $type = JsonVarType::OBJECT,
        private ?string     $title = null,
        private ?string     $description = null,
    ){

    }

    final public function dump(): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
        ];
    }
}
