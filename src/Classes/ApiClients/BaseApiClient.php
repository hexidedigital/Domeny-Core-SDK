<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\ApiClients;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Hexidedigital\DomenyCoreSdk\Classes\Adapters\ResponseAdapter;

abstract class BaseApiClient
{
    protected Client $client;

    protected array $whereConditions = [];

    protected string $type;
    protected ?string $adapterClass;

    public function __construct(string $type, ?string $adapterClass = null)
    {
        $this->type = $type;
        $this->adapterClass = $adapterClass;
        $this->client = new Client([
            'base_uri' => config('domeny-sdk.base_uri'),
        ]);
    }

    public function get()
    {
//        dd($this->getConditions());
        try {
            $response = $this->client->post("api/v1/$this->type/query", [
                'json' => [
                    'conditions' => $this->getConditions(),
                    'query_method' => 'get',
                ]
            ]);
            $data = json_decode($response->getBody()->getContents(), true);

            $classString = $this->adapterClass;
            $data = \Arr::map($data, fn ($item) => $classString::fromArray($item));
        } catch (ClientException $exception) {
            dd($exception->getResponse()->getBody()->getContents());
            return ResponseAdapter::fromError($exception);
        }
        dd($data);
    }
    public function first()
    {
        try {
            $response = $this->client->post("api/v1/$this->type/query", [
                'json' => [
                    'conditions' => $this->getConditions(),
                    'query_method' => 'first',
                ]
            ]);
        } catch (ClientException $exception) {
            dd($exception->getResponse()->getBody()->getContents());
            return ResponseAdapter::fromError($exception);
        }
        dd($response->getBody()->getContents());
    }

    public function getConditions(): array
    {
        $conditions = [];
        foreach ($this->whereConditions as $type => $whereCondition) {
            $conditions[] = $this->parseConditions($type, $whereCondition);
        }
        return $conditions;
    }

    protected function parseConditions($type, array $whereCondition): array
    {
        if ($this->isSimpleCondition($whereCondition)) {
            return $whereCondition;
        }

        if (is_callable($whereCondition['column'])) {
            return [
                'operator' => 'custom',
                'value' => call_user_func($whereCondition['column'], ((new static())))->getConditions(),
                'boolean' => $whereCondition['boolean'],
            ];
        }
    }

    protected function isSimpleCondition(array $whereCondition): bool
    {
        return !is_callable($whereCondition['column'])
            && (!is_array($whereCondition['value'] ?? null) || $whereCondition['operator'] != 'in');
    }

    public function where($column, $operator = null, $value = null, $boolean = 'and'): static
    {
        if (is_null($value) && !empty($operator)) {
            $value = $operator;
            $operator = '=';
        }

        if (!empty($value)) {
            $this->whereConditions[] = [
                'column' => $column,
                'operator' => $operator,
                'value' => $value,
                'boolean' => $boolean,
            ];
        } else {
            $this->whereConditions[] = [
                'column' => $column,
                'operator' => null,
                'value' => null,
                'boolean' => $boolean,
            ];
        }

        return $this;
    }

    public function orWhere($column, $operator = null, $value = null): static
    {
        return $this->where($column, $operator, $value, 'or');
    }
}