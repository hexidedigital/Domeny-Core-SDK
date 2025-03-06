<?php

declare(strict_types=1);

namespace Hexidedigital\DomenyCoreSdk\Enums;

use ArchTech\Enums\InvokableCases;

enum UserRoles: string
{
    use InvokableCases;

    case ADMIN = 'admin';

    case USER = 'user';

    public static function isAdmin(string $slug): bool
    {
        return in_array($slug, self::getAdmins());
    }

    public static function getAdmins(): array
    {
        return [
            self::ADMIN->value,
        ];
    }
}
