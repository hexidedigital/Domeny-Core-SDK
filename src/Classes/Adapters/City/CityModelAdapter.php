<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\Adapters\City;

use Hexidedigital\DomenyCoreSdk\Classes\Adapters\BaseAdapter;

class CityModelAdapter extends BaseAdapter
{
    public function __construct(
        public ?int $id,
        public ?string $title,
    ) {

    }
}