<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\ApiClients;

use Hexidedigital\DomenyCoreSdk\Classes\Adapters\DomainZones\DomainZoneModelAdapter;

/**
 * @extends BaseApiClient<DomainZoneModelAdapter>
 */
class DomainZoneApiClient extends BaseApiClient
{
    public function __construct()
    {
        parent::__construct('domain-zone', DomainZoneModelAdapter::class);
    }
}