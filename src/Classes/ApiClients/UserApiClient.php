<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\ApiClients;

use Hexidedigital\DomenyCoreSdk\Classes\Adapters\Users\UserModelAdapter;

/**
 * @extends BaseApiClient<UserModelAdapter>
 */
class UserApiClient extends BaseApiClient
{
    public function __construct()
    {
        parent::__construct('user', UserModelAdapter::class);
    }
}