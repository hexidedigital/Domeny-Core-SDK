<?php

return [
    'base_uri' => env('DOMENY_CORE_BASE_URI', ''),

    'paths' => [
        'domains' => [
            'index' => 'api/v1/domains',
        ],
    ],

    'throw_exception_for_adapters' => false,
];