<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\Adapters\Users;

use Carbon\Carbon;
use Hexidedigital\DomenyCoreSdk\Classes\Adapters\BaseAdapter;
use Hexidedigital\DomenyCoreSdk\Classes\Adapters\Country\CountryModelAdapter;

class Profile extends BaseAdapter
{
    public function __construct(
        public ?int $id,
        public ?int $user_id,
        public ?string $first_name,
        public ?string $last_name,
        public ?string $photo,
        public ?string $company_name,
        public ?string $company_registration_code,
        public ?string $company_vat,
        public ?string $company_country_id,
        public ?string $company_address,
        public ?string $company_phone,
        public ?string $company_email,
        public ?string $company_website,
        public ?Carbon $created_at,
        public ?Carbon $updated_at,
        public ?Carbon $deleted_at,

//        public ?CountryModelAdapter $country,
    ) {

    }
}