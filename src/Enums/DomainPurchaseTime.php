<?php

declare(strict_types=1);

namespace Hexidedigital\DomenyCoreSdk\Enums;

use App\Helpers\TranslationHelper;
use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Options;
use Exception;
use Illuminate\Support\Carbon;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

enum DomainPurchaseTime: string
{
    use InvokableCases;
    use Options;
    case MONTH = 'month';
    case YEAR = 'year';


    const REQUEST_OPTIONS = ['month', 'year'];

    #[Schema(
        schema: 'DomainPurchaseTimeResource',
        properties: [
            new Property(
                property: 'key',
                title: 'Key',
                type: 'string',
                enum: ['month', 'year'],
                readOnly: true,
                example: 'month',
                nullable: true
            ),
            new Property(property: 'title', title: 'Title', type: 'string', readOnly: true, example: '1 month'),
        ],
    )]
    public function getResource(): array
    {
        return [
            'key' => $this->value,
            'title' => TranslationHelper::translate("admin_labels.domain_purchase_times.$this->value")
        ];
    }

    public static function getCollection(): array
    {
        $data = [];
        foreach (self::cases() as $case) {
            $data[] = $case->getResource();
        }

        return ['data' => $data];
    }

    public static function calculatePrice(string $type, int|float $price): int|float
    {
        return match ($type) {
            self::MONTH->value => $price,
            self::YEAR->value => $price * 12,
            default => throw new \RuntimeException("Unknown type: $type"),
        };
    }

    public function getExpiresAt(): Carbon
    {
        return match ($this) {
            self::MONTH => now()->addMonth(),
            self::YEAR => now()->addYear(),
            default => throw new Exception("Unknown expires_at for: $this->value"),
        };
    }
}
