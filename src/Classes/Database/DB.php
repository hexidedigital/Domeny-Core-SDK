<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\Database;

class DB
{
    public static function raw(string $value): DatabaseRawValue
    {
        return new DatabaseRawValue($value);
    }
}