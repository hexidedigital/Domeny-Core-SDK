<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\ApiClients;

use Hexidedigital\DomenyCoreSdk\Classes\Adapters\Country\CountryModelAdapter;

/**
 * @extends BaseApiClient<CountryModelAdapter>
 */
class CountryApiClient extends BaseApiClient
{
    public function __construct()
    {
        parent::__construct('country', config('domeny-sdk.adapters.country'));
    }
}