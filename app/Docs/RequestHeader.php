<?php

namespace App\Docs;

use App\Docs\Schemas\SchemaInterface;
use Attribute;

#[Attribute]
readonly class RequestHeader implements Dumpable
{
    public function __construct(
        private string          $name,
        private string          $description,
        private SchemaInterface $schema,
        private ?string         $example = null,
        private bool            $required = false,
        private bool            $deprecated = false,
        private bool            $shouldMask = false,
    ) {
    }

    public static function mask(): string
    {
        return '<masked>';
    }

    public function dump(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'required' => $this->required,
            'deprecated' => $this->deprecated,
            'example' => $this->example ? sprintf(
                '%s: %s',
                $this->name,
                $this->shouldMask ? $this->example : self::mask(),
            ) : null,
            'in' => 'header',
            'schema' => $this->schema->dump(),
            'x-masked' => $this->shouldMask,
        ];
    }
}
