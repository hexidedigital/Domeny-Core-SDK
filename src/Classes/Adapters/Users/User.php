<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\Adapters\Users;

use Carbon\Carbon;
use Hexidedigital\DomenyCoreSdk\Classes\Adapters\BaseAdapter;
use Hexidedigital\DomenyCoreSdk\Classes\ApiClients\BaseApiClient;
use Illuminate\Contracts\Auth\Authenticatable;

class User extends BaseAdapter implements Authenticatable
{
    public function __construct(
        public ?int $id,
        public ?string $email,
        public ?string $password,
        public ?Carbon $email_verified_at,
        public ?Carbon $created_at,
        public ?Carbon $updated_at,
        public ?Carbon $deleted_at,

        public ?Profile $profile,
    ) {

    }


    /**
     * @return BaseApiClient<Profile>
     */
    public function profile(): BaseApiClient
    {
        return $this->hasOne(Profile::class);
    }

    // Auth

    public function getAuthIdentifierName()
    {
        return 'id';
    }

    public function getAuthIdentifier()
    {
        return $this->{$this->getAuthIdentifierName()};
    }

    public function getAuthPasswordName()
    {
        return 'password';
    }

    public function getAuthPassword()
    {
        return $this->{$this->getAuthPasswordName()};
    }

    public function getRememberToken()
    {
        if (! empty($this->getRememberTokenName())) {
            return (string) $this->{$this->getRememberTokenName()};
        }
    }

    public function setRememberToken($value)
    {
        if (! empty($this->getRememberTokenName())) {
            $this->{$this->getRememberTokenName()} = $value;
        }
    }

    public function getRememberTokenName()
    {
        return null;
    }
}