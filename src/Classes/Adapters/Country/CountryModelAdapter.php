<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\Adapters\Country;

use Carbon\Carbon;
use Hexidedigital\DomenyCoreSdk\Classes\Adapters\BaseAdapter;
use Hexidedigital\DomenyCoreSdk\Classes\ApiClients\BaseApiClient;
use Hexidedigital\DomenyCoreSdk\Classes\ApiClients\CountryApiClient;

class CountryModelAdapter extends BaseAdapter
{
    public function __construct(
        public ?int $id,
        public ?string $title,
        public ?string $country_code,

        public ?Carbon $created_at,
        public ?Carbon $updated_at,
    ) {

    }

    protected static function getApi(): BaseApiClient
    {
        return new CountryApiClient;
    }
}