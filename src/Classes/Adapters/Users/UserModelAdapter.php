<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\Adapters\Users;

use Carbon\Carbon;
use Hexidedigital\DomenyCoreSdk\Classes\Adapters\BaseAdapter;
class UserModelAdapter extends BaseAdapter
{
    public function __construct(
        public int $id,
        public string $email,
        public ?Carbon $email_verified_at,
        public Carbon $created_at,
        public Carbon $updated_at,
        public ?Carbon $deleted_at,

        public ?ProfileModelAdapter $profile,
    ) {

    }
}