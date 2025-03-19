<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\ApiClients;

/**
 * @template U
 * @extends BaseApiClient<U>
 */
class BelongsToMany extends BaseApiClient
{
    public const IS_ARRAY = true;

    public const METHOD_NAME = 'belongsToMany';
}