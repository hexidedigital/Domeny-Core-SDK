<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\Adapters\DomainZones;

use Carbon\Carbon;
use Hexidedigital\DomenyCoreSdk\Classes\Adapters\BaseAdapter;
use Hexidedigital\DomenyCoreSdk\Classes\Adapters\City\CityModelAdapter;
use Hexidedigital\DomenyCoreSdk\Classes\ApiClients\BaseApiClient;

class DomainZoneModelAdapter extends BaseAdapter
{
    public function __construct(
        public ?int $id,
        public ?string $name,
        public ?string $price,
        public ?string $price_coins,
        public ?Carbon $created_at,
        public ?Carbon $updated_at,
    ) {
    }

    public function cities(): BaseApiClient
    {
        return $this->belongsToMany(CityModelAdapter::class, 'domain_zone_city');
    }
}