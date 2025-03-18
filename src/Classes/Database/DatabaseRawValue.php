<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\Database;

class DatabaseRawValue
{
    public function __construct(public string $value)
    {

    }

    public function __toString(): string
    {
        return $this->value;
    }
}