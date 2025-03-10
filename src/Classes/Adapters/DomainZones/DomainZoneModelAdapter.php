<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\Adapters\DomainZones;

use Carbon\Carbon;
use Hexidedigital\DomenyCoreSdk\Classes\Adapters\BaseAdapter;

class DomainZoneModelAdapter extends BaseAdapter
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $price,
        public ?string $price_coins,
        public ?Carbon $created_at,
        public ?Carbon $updated_at,
    ) {
    }
}