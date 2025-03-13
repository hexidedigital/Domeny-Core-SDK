<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\Adapters\Domains;

use Carbon\Carbon;
use Hexidedigital\DomenyCoreSdk\Classes\Adapters\BaseAdapter;
use Hexidedigital\DomenyCoreSdk\Classes\Adapters\DomainZones\DomainZoneModelAdapter;
use Hexidedigital\DomenyCoreSdk\Enums\DomainStatus;

class DomainModelAdapter extends BaseAdapter
{
    public function __construct(
        public ?int $id,
        public ?int $domain_zone_id,
        public ?string $name,
        public ?int $project_id,
        public ?int $user_id,
        public ?DomainStatus $status,
        public ?Carbon $purchased_at,
        public ?Carbon $expires_at,
        public ?int $visits_count,
        public ?bool $user_status,
        public ?string $price,
        public ?string $price_coins,
        public ?Carbon $created_at,
        public ?Carbon $updated_at,
        
        public ?DomainZoneModelAdapter $domain_zone,
    ) {

    }
}