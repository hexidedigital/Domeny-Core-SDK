<?php

declare(strict_types=1);

namespace Hexidedigital\DomenyCoreSdk\Enums;

use App\Helpers\TranslationHelper;
use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Options;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

enum OrderStatus: string
{
    use InvokableCases;
    use Options;

    case NEW = 'new'; // New

    case PENDING_PAYMENT = 'pending_payment'; // Awaiting Payment
    case COMPLETED = 'completed'; // Payment was successful, user now owns everything he ordered
    case FAILED = 'failed'; // Payment failed or something went wrong
    case CANCELLED = 'cancelled';

    #[Schema(
        schema: 'OrderStatusResource',
        properties: [
            new Property(
                property: 'key',
                title: 'Status Key',
                type: 'string',
                enum: ['new', 'pending_payment', 'completed', 'failed', 'cancelled'],
                readOnly: true,
                example: 'available',
                nullable: true
            ),
            new Property(property: 'title', title: 'Status title', type: 'string', readOnly: true, example: 'Available'),
        ],
    )]
    public function getResource(): array
    {
        return [
            'key' => $this->value,
            'title' => TranslationHelper::translate("admin_labels.domain_statuses.$this->value")
        ];
    }
}
