<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\Adapters\Domains;

use Carbon\Carbon;
use Hexidedigital\DomenyCoreSdk\Enums\DomainStatus;

class DomainModelAdapter
{
    public function __construct(
        public int $id,
        public int $domain_zone_id,
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
    ) {

    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            domain_zone_id: $data['domain_zone_id'],
            name: $data['name'] ?? null,
            project_id: $data['project_id'] ?? null,
            user_id: $data['user_id'] ?? null,
            status: empty($data['status']) ? null : DomainStatus::from($data['status']),
            purchased_at: empty($data['purchased_at']) ? null : Carbon::parse($data['purchased_at']),
            expires_at: empty($data['expires_at']) ? null : Carbon::parse($data['expires_at']),
            visits_count: $data['visits_count'] ?? null,
            user_status: $data['user_status'] ?? null,
            price: $data['price'] ?? null,
            price_coins: $data['price_coins'] ?? null,
            created_at: empty($data['created_at']) ? null : Carbon::parse($data['created_at']),
            updated_at: empty($data['updated_at']) ? null : Carbon::parse($data['updated_at']),
        );
    }
}