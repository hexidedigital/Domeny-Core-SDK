<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\ApiClients;

use Arr;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Hexidedigital\DomenyCoreSdk\Exceptions\ErrorResponseException;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @template T
 */
abstract class BaseApiClient
{
    protected Client $client;
    protected array $rawResponseMethods = [
        'exists',
        'delete',
    ];

    protected array $parentRelationData = [];
    protected array $whereConditions = [];
    protected array $loadingRelations = [];
    protected array $order = [];
    protected ?int $limit = null;

    protected string $apiPath;
    protected string $type;
    protected ?string $adapterClass;

    public function __construct(string $type, ?string $adapterClass = null, ?string $apiPath = null)
    {
        $this->type = $type;
        $this->adapterClass = $adapterClass;
        $this->client = new Client([
            'base_uri' => config('domeny-sdk.base_uri'),
        ]);
        $this->apiPath = $apiPath ?? "api/v1/$this->type/query";
    }

    /**
     * @return $this
     */
    public function clearQuery(): static
    {
        $this->whereConditions = [];
        $this->loadingRelations = [];
        $this->order = [];
        $this->limit = null;

        return $this;
    }

    /**
     * @param string $query_method
     * @param array $additional
     * @param bool $isSingleElement
     * @return T|T[]|LengthAwarePaginator<T>|bool
     * @throws GuzzleException
     * @throws ErrorResponseException
     */
    private function runQuery(string $query_method, array $additional = [], bool $isSingleElement = false)
    {
        try {
            $conditions = $this->getConditions();
            $relations = $this->getRelations();
            $order = $this->getOrder();
            $limit = $this->getLimit();
            $response = $this->client->post($this->apiPath, [
                'json' => [
                    'conditions' => $conditions,
                    'query_method' => $query_method,
                    'relations' => $relations,
                    'order' => $order,
                    'limit' => $limit,
                    ...$additional
                ],
                'headers' => [
                    'X-localization' => app()->getLocale(),
                ]
            ]);
            $data = json_decode($response->getBody()->getContents(), true);

            $this->clearQuery();
            $classString = $this->adapterClass;
            if (in_array($query_method, $this->rawResponseMethods) || empty($classString) || !method_exists($classString, 'fromArray')) {
                return $data;
            }
            if (!$isSingleElement) {
                if ($query_method == 'paginate') {
                    return new LengthAwarePaginator(
                        items: Arr::map($data['data'], fn ($item) => call_user_func("$classString::fromArray", $item, $relations)),
                        total: $data['total'] ?? null,
                        perPage: $data['per_page'] ?? null,
                        currentPage: $data['current_page'] ?? null,
                        options: $data['options'] ?? []
                    );
                }

                return Arr::map($data, fn ($item) => call_user_func("$classString::fromArray", $item, $relations));
            } else {
                if (empty($data)) {
                    return null;
                }
                return call_user_func("$classString::fromArray", $data, $relations);
            }
        } catch (ClientException $exception) {
            $this->clearQuery();
            throw new ErrorResponseException($exception->getResponse());
        }
    }

    /**
     * @param string $query_method
     * @param array $data
     * @param bool $rawResult
     * @return T|null
     * @throws ErrorResponseException
     * @throws GuzzleException
     */
    public function runChangeData(string $query_method, array $data, bool $rawResult = false)
    {
        $data = array_merge($this->parentRelationData, $data);
        $conditions = $this->getConditions();

        try {
            $response = $this->client->post($this->apiPath, [
                'json' => [
                    'query_method' => $query_method,
                    'conditions' => $conditions,
                    'data' => $data,
                ]
            ]);
            $data = json_decode($response->getBody()->getContents(), true);

            if (empty($data)) {
                return null;
            }

            if ($rawResult) {
                return $data;
            }

            $classString = $this->adapterClass;

            return call_user_func("$classString::fromArray", $data);
        } catch (ClientException $exception) {
            $this->clearQuery();
            throw new ErrorResponseException($exception->getResponse());
        }
    }

    /**
     * @param array $data
     * @return T
     * @throws ErrorResponseException
     * @throws GuzzleException
     */
    public function create(array $data)
    {
        return $this->runChangeData("create", $data);
    }

    /**
     * @param array $data
     * @return bool
     * @throws ErrorResponseException
     * @throws GuzzleException
     */
    public function update(array $data): bool
    {
        return (bool) $this->runChangeData('update', $data, rawResult: true);
    }

    /**
     * @param $id
     * @param array $data
     * @return T
     * @throws ErrorResponseException
     * @throws GuzzleException
     */
    public function updateSingle($id, array $data)
    {
        return $this->where('id', $id)->runChangeData('updateSingle', $data);
    }

    /**
     * @return T[]
     * @throws ErrorResponseException
     * @throws GuzzleException
     */
    public function get(): array
    {
        return $this->runQuery('get');
    }

    /**
     * @return bool
     * @throws ErrorResponseException
     * @throws GuzzleException
     */
    public function exists(): bool
    {
        return (bool) $this->runQuery('exists');
    }

    /**
     * @return bool
     * @throws ErrorResponseException
     * @throws GuzzleException
     */
    public function delete(): bool
    {
        return (bool) $this->runQuery('delete');
    }

    /**
     * @return T
     * @throws ErrorResponseException
     * @throws GuzzleException
     */
    public function first(): mixed
    {
        return $this->runQuery('first', isSingleElement: true);
    }

    /**
     * @param int $perPage
     * @param int $page
     * @return LengthAwarePaginator<T>
     * @throws ErrorResponseException
     * @throws GuzzleException
     */
    public function paginate(int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->runQuery('paginate', ['pagination' => ['per_page' => $perPage, 'page' => $page]]);
    }

    /**
     * @param int|null $limit
     * @return $this
     */
    public function limit(?int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * @return array
     */
    public function getOrder(): array
    {
        return $this->order;
    }

    /**
     * @return array
     */
    public function getRelations(): array
    {
        $relations = [];
        foreach ($this->loadingRelations as $relationData) {
            foreach ($relationData as $relation => $closure) {
                if (is_numeric($relation) && is_string($closure)) {
                    $relation = $closure;
                    $closure = null;
                }

                if (is_null($closure) || !is_callable($closure)) {
                    $relations[] = ['relation' => $relation, 'closure' => null];
                    continue;
                }

                $query = call_user_func($closure, new static());

                $relations[] = [
                    'relation' => $relation,
                    'conditions' => $query->getConditions(),
                    'relations' => $query->getRelations(),
                    'order' => $query->getOrder(),
                    'limit' => $this->getLimit(),
                ];
            }
        }
        return $relations;
    }

    /**
     * @return array
     */
    public function getConditions(): array
    {
        $conditions = [];
        foreach ($this->whereConditions as $whereCondition) {
            $conditions[] = $this->parseConditions($whereCondition);
        }
        return $conditions;
    }

    /**
     * @param array $whereCondition
     * @return array
     */
    protected function parseConditions(array $whereCondition): array
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
//        dd($whereCondition);
    }

    /**
     * @param array $whereCondition
     * @return bool
     */
    protected function isSimpleCondition(array $whereCondition): bool
    {
        return !is_callable($whereCondition['column'])
            && (!is_array($whereCondition['value'] ?? null) || $whereCondition['operator'] == 'in');
    }

    public function with($relations, $callback = null): static
    {
        if (is_array($relations)) {
            $this->loadingRelations[] = $relations;
        } else {
            $this->loadingRelations[] = [$relations => $callback];
        }
        return $this;
    }

    /**
     * @param $column
     * @param null $operator
     * @param null $value
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and', bool $not = false): static
    {
        if (is_null($value) && !empty($operator) && func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        if (!empty($value)) {
            $this->whereConditions[] = [
                'column' => $column,
                'operator' => $operator,
                'value' => $value,
                'boolean' => $boolean,
                'not' => $not,
            ];
        } else {
            $this->whereConditions[] = [
                'column' => $column,
                'operator' => null,
                'value' => null,
                'boolean' => $boolean,
                'not' => $not,
            ];
        }

        return $this;
    }

    /**
     * @param $column
     * @param $operator
     * @param $value
     * @param $boolean
     * @return $this
     */
    public function whereNot($column, $operator = null, $value = null, $boolean = 'and'): static
    {
        if (is_null($value) && !empty($operator) && func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        return $this->where($column, $operator, $value, $boolean, true);
    }

    /**
     * @param $column
     * @param null $operator
     * @param null $value
     * @param bool $not
     * @return $this
     */
    public function orWhere($column, $operator = null, $value = null, bool $not = false): static
    {
        return $this->where($column, $operator, $value, 'or', $not);
    }

    /**
     * @param $column
     * @param $operator
     * @param $value
     * @return $this
     */
    public function orWhereNot($column, $operator = null, $value = null): static
    {
        if (is_null($value) && !empty($operator) && func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        return $this->orWhere($column, $operator, $value, true);
    }

    /**
     * @param $column
     * @param array $values
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function whereIn($column, array $values = [], $boolean = 'and', bool $not = false): static
    {
        return $this->where($column, 'in', $values, $boolean, $not);
    }

    /**
     * @param $column
     * @param array $values
     * @param $boolean
     * @return $this
     */
    public function whereNotIn($column, array $values = [], $boolean = 'and'): static
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    /**
     * @param $column
     * @param array $values
     * @param bool $not
     * @return $this
     */
    public function orWhereIn($column, array $values = [], bool $not = false): static
    {
        return $this->whereIn($column, $values, 'or', $not);
    }

    /**
     * @param $column
     * @param array $values
     * @return $this
     */
    public function orWhereNotIn($column, array $values = []): static
    {
        return $this->orWhereIn($column, $values, true);
    }

    /**
     * @param $column
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function whereNull($column, $boolean = 'and', bool $not = false): static
    {
        return $this->where($column, null, null, $boolean, $not);
    }

    /**
     * @param $column
     * @param $boolean
     * @return $this
     */
    public function whereNotNull($column, $boolean = 'and'): static
    {
        return $this->whereNull($column, $boolean, true);
    }

    /**
     * @param $column
     * @param bool $not
     * @return $this
     */
    public function orWhereNull($column, bool $not = false): static
    {
        return $this->whereNull($column, 'or', $not);
    }

    /**
     * @param $column
     * @return $this
     */
    public function orWhereNotNull($column): static
    {
        return $this->orWhereNull($column, true);
    }

    /**
     * @param $column
     * @param $direction
     * @return $this
     */
    public function orderBy($column, $direction = 'asc'): static
    {
        $this->order[] = [
            'column' => $column,
            'direction' => $direction,
        ];

        return $this;
    }

    /**
     * @param $column
     * @return $this
     */
    public function orderByDesc($column): static
    {
        return $this->orderBy($column, 'desc');
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setParentRelationData(array $data)
    {
        $this->parentRelationData = $data;
        return $this;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name): mixed
    {
        if (isset($this->{$name})) {
            return $this->{$name};
        }

        return null;
    }

    private function blackJack()
    {
        return 'blackJack';
    }

    private function hookers()
    {
        return 'hookers';
    }
}