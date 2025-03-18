<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\Adapters\City;

use Hexidedigital\DomenyCoreSdk\Classes\Adapters\BaseAdapter;
use Hexidedigital\DomenyCoreSdk\Classes\ApiClients\BaseApiClient;
use Hexidedigital\DomenyCoreSdk\Classes\ApiClients\CityApiClient;

class CityModelAdapter extends BaseAdapter
{
    public function __construct(
        public ?int $id,
        public ?string $title,
    ) {

    }

    protected static function getApi(): BaseApiClient
    {
        return new CityApiClient;
    }
}