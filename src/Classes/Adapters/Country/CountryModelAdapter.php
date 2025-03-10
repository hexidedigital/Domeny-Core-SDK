<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\Adapters\Country;

use Carbon\Carbon;
use Hexidedigital\DomenyCoreSdk\Classes\Adapters\BaseAdapter;

class CountryModelAdapter extends BaseAdapter
{
    public function __construct(
        public int $id,
        public string $title,
    ) {

    }
}