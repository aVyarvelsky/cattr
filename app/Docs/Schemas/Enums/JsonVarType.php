<?php

namespace App\Docs\Schemas\Enums;

enum JsonVarType: string
{
    case ARRAY = 'array';
    case BOOLEAN = 'boolean';
    case NUMBER = 'number';
    case OBJECT = 'object';
    case STRING = 'string';
}
