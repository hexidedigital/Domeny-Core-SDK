<?php

declare(strict_types=1);

namespace Hexidedigital\DomenyCoreSdk\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Options;
use App\Helpers\TranslationHelper;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

enum DomainStatus: string
{
    use InvokableCases;
    use Options;

    case RESERVED = 'reserved'; // Reserved means user added this domain to a cart, but not bought it yet

    case ACTIVE = 'active'; // User has paid for the domain
    case INACTIVE = 'inactive'; // User failed to pay for the domain, but it is still reserved
    case AVAILABLE = 'available'; // User failed to pay for the domain and now it is available

    #[Schema(
        schema: 'DomainStatusResource',
        properties: [
            new Property(
                property: 'key',
                title: 'Status Key',
                type: 'string',
                enum: ['reserved', 'active', 'inactive', 'available'],
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
