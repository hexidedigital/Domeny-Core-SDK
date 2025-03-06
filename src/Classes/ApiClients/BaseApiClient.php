<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\ApiClients;

use GuzzleHttp\Client;

abstract class BaseApiClient
{
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('domeny-sdk.base_uri'),
        ]);
    }
}