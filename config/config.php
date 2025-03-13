<?php

return [
    'base_uri' => env('DOMENY_CORE_BASE_URI', ''),

    'paths' => [
        'domains' => [
            'index' => 'api/v1/domains',
        ],
    ],

    'throw_exception_for_adapters' => false,
    'adapters' => [
        'user' => \Hexidedigital\DomenyCoreSdk\Classes\Adapters\Users\UserModelAdapter::class,
        'profile' => \Hexidedigital\DomenyCoreSdk\Classes\Adapters\Users\ProfileModelAdapter::class,
        'country' => \Hexidedigital\DomenyCoreSdk\Classes\Adapters\Country\CountryModelAdapter::class,
        'city' => \Hexidedigital\DomenyCoreSdk\Classes\Adapters\City\CityModelAdapter::class,
        'specialization' => \Hexidedigital\DomenyCoreSdk\Classes\Adapters\Specialization\SpecializationModelAdapter::class,
    ],
];