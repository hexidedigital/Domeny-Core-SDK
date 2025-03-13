<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\Adapters\Specialization;

use Hexidedigital\DomenyCoreSdk\Classes\Adapters\BaseAdapter;

class SpecializationModelAdapter extends BaseAdapter
{
    public function __construct(
        public ?int $id,
        public ?string $title,
    ) {

    }
}