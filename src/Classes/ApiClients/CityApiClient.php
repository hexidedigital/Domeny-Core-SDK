<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\ApiClients;

use Hexidedigital\DomenyCoreSdk\Classes\Adapters\City\CityModelAdapter;

/**
 * @extends BaseApiClient<CityModelAdapter>
 */
class CityApiClient extends BaseApiClient
{
    public function __construct()
    {
        parent::__construct('city', config('domeny-sdk.adapters.city'));
    }
}