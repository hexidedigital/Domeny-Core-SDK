<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\ApiClients;

use Hexidedigital\DomenyCoreSdk\Classes\Adapters\Domains\DomainModelAdapter;
/**
 * @extends BaseApiClient<DomainModelAdapter>
 */
class DomainApiClient extends BaseApiClient
{
    public function __construct()
    {
        parent::__construct('domain', DomainModelAdapter::class);
    }
}