<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\ApiClients;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Hexidedigital\DomenyCoreSdk\Classes\Adapters\ResponseAdapter;
use Hexidedigital\DomenyCoreSdk\Classes\Adapters\Users\UserModelAdapter;

class UserApiClient extends BaseApiClient
{
    public function __construct()
    {
        parent::__construct('user', UserModelAdapter::class);
    }

    /**
     * @return ResponseAdapter<UserModelAdapter>
     * @throws GuzzleException
     * @throws Exception
     */
    public function show(int $id): ResponseAdapter
    {
        try {
            $response = $this->client->get("api/v1/user/$id");
        } catch (ClientException $exception) {
            return ResponseAdapter::fromError($exception);
        }

        return ResponseAdapter::fromResponse(
            $response,
            fn ($data) => UserModelAdapter::fromArray($data),
        );
    }

    /**
     * @return ResponseAdapter<UserModelAdapter>
     * @throws GuzzleException
     * @throws Exception
     */
    public function login(string $email, string $password): ResponseAdapter
    {
        try {
            $response = $this->client->post('api/v1/user/login', [
                'json' => [
                    'email' => $email,
                    'password' => $password,
                ]
            ]);
        } catch (ClientException $exception) {
            return ResponseAdapter::fromError($exception);
        }
//        dd(json_decode($response->getBody()->getContents(), true));
        return ResponseAdapter::fromResponse(
            $response,
            fn ($data) => UserModelAdapter::fromArray($data),
        );
    }
}