<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\ApiClients;

use Hexidedigital\DomenyCoreSdk\Classes\Adapters\Specialization\SpecializationModelAdapter;

/**
 * @extends BaseApiClient<SpecializationModelAdapter>
 */
class SpecializationApiClient extends BaseApiClient
{
    public function __construct()
    {
        parent::__construct('specialization', config('domeny-sdk.adapters.specialization'));
    }
}