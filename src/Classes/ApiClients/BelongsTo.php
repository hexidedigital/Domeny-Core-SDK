<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\ApiClients;

/**
 * @template U
 * @extends BaseApiClient<U>
 */
class BelongsTo extends BaseApiClient
{
    public const IS_ARRAY = false;

    public const METHOD_NAME = 'belongsTo';
}