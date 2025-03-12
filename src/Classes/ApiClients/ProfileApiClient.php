<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\ApiClients;

use Hexidedigital\DomenyCoreSdk\Classes\Adapters\Users\UserModelAdapter;

/**
 * @extends BaseApiClient<UserModelAdapter>
 */
class ProfileApiClient extends BaseApiClient
{
    public function __construct()
    {
        parent::__construct('profile', config('domeny-sdk.adapters.profile'));
    }
}