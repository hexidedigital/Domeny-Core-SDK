<?php

namespace Hexidedigital\DomenyCoreSdk\Extensions;

use Closure;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Hexidedigital\DomenyCoreSdk\Classes\Adapters\Users\UserModelAdapter;
use Hexidedigital\DomenyCoreSdk\Classes\ApiClients\UserApiClient;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Hash;

class ApiUserAuthProvider implements UserProvider
{
    public function retrieveById($identifier)
    {
        try {
            return $this->getUserApi()->where('id', $identifier)->first();
        } catch (Exception|GuzzleException $e) {
            report($e);
            return null;
        }
    }

    public function retrieveByToken($identifier, $token)
    {
        try {
            $user = $this->getUserApi()->where('id', $identifier)->first();
        } catch (Exception|GuzzleException $e) {
            report($e);
            return null;
        }

        $rememberToken = $user->remember_token;

        return $rememberToken && hash_equals($rememberToken, $token) ? $user : null;
    }

    /**
     * @param UserModelAdapter $user
     * @param $token
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        $user->setRememberToken($token);
    }

    public function retrieveByCredentials(array $credentials)
    {
        $credentials = array_filter(
            $credentials,
            fn ($key) => ! str_contains($key, 'password'),
            ARRAY_FILTER_USE_KEY
        );

        if (empty($credentials)) {
            return;
        }

        // First we will add each credential element to the query as a where clause.
        // Then we can execute the query and, if we found a user, return it in a
        // Eloquent User "model" that will be utilized by the Guard instances.
        $query = $this->getUserApi();

        foreach ($credentials as $key => $value) {
            if (is_array($value) || $value instanceof Arrayable) {
                $query->whereIn($key, $value);
            } elseif ($value instanceof Closure) {
                $value($query);
            } else {
                $query->where($key, $value);
            }
        }

        return $query->first();
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        if (is_null($plain = $credentials['password'])) {
            return false;
        }

        return Hash::check($plain, $user->getAuthPassword());
    }

    public function rehashPasswordIfRequired(
        Authenticatable $user,
        array $credentials,
        bool $force = false
    ) {
        // TODO: Implement rehashPasswordIfRequired() method.
    }

    protected function getUserApi(): UserApiClient
    {
        return new UserApiClient();
    }
}