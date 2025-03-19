<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\ApiClients;

/**
 * @template U
 * @extends BaseApiClient<U>
 */
class HasOne extends BaseApiClient
{
    public const IS_ARRAY = false;
    public const METHOD_NAME = 'hasOne';

    protected bool $multiple = false;

    public function processAsMultiple()
    {
        $this->multiple = true;
        return $this;
    }

    public function isMultiple()
    {
        return $this->multiple;
    }
}