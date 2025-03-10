<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\ApiClients;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Hexidedigital\DomenyCoreSdk\Classes\Adapters\Domains\DomainModelAdapter;
use Hexidedigital\DomenyCoreSdk\Classes\Adapters\PaginatedResponseAdapter;
use Hexidedigital\DomenyCoreSdk\Classes\Adapters\ResponseAdapter;
use Illuminate\Support\Arr;

class DomainApiClient extends BaseApiClient
{
    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function all(
        ?string $search = null,
        ?int $countryId = null,
        array $cities = [],
        array $specializations = []
    ) {
        $response = $this->client->get('api/v1/domains', [
            'query' => $this->prepareFilterQuery(
                paginate: false,
                search: $search,
                countryId: $countryId,
                cities: $cities,
                specializations: $specializations
            )
        ]);

        return ResponseAdapter::fromResponse(
            $response,
            fn ($data) => Arr::map($data, function ($item) {
                return DomainModelAdapter::fromArray($item);
            })
        );
    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function paginate(
        int $perPage = 15,
        int $page = 1,
        ?string $search = null,
        ?int $countryId = null,
        array $cities = [],
        array $specializations = []
    ) {
        $response = $this->client->get('api/v1/domains', [
            'query' => $this->prepareFilterQuery(
                perPage: $perPage,
                page: $page,
                paginate: true,
                search: $search,
                countryId: $countryId,
                cities: $cities,
                specializations: $specializations
            )
        ]);

        return PaginatedResponseAdapter::fromResponse(
            $response,
            function ($data) {
                $data['data'] = Arr::map($data['data'], function ($item) {
                    return DomainModelAdapter::fromArray($item);
                });
                return $data;
            }
        );
    }

    protected function prepareFilterQuery(
        int $perPage = 15,
        int $page = 1,
        bool $paginate = false,
        ?string $search = null,
        ?int $countryId = null,
        array $cities = [],
        array $specializations = []
    ): array {
        $query = [
            'paginate' => $paginate,
        ];

        if ($paginate) {
            $query['per_page'] = $perPage;
            $query['page'] = $page;
        }

        if (!is_null($countryId)) {
            $query['country_id'] = $countryId;
        }

        if (!is_null($search)) {
            $query['query'] = $search;
        }

        if (!empty($cities)) {
            $query['cities'] = implode(',', $cities);
        }

        if (!empty($specializations)) {
            $query['specializations'] = implode(',', $specializations);
        }

        return $query;
    }
}