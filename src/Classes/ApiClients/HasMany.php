<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\ApiClients;

use ReflectionClass;

/**
 * @template U
 * @extends BaseApiClient<U>
 */
class HasMany extends BaseApiClient
{
    public const IS_ARRAY = true;

    public const METHOD_NAME = 'hasMany';

    public function one(): HasOne
    {
        return (new HasOne(
            $this->type,
            $this->adapterClass,
            $this->apiPath,
            $this->getParentData()
        ))->processAsMultiple();
    }
}
