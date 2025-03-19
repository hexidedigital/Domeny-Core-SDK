<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\ApiClients;

use Arr;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Hexidedigital\DomenyCoreSdk\Classes\Database\DatabaseRawValue;
use Hexidedigital\DomenyCoreSdk\Classes\Database\DB;
use Hexidedigital\DomenyCoreSdk\Exceptions\ErrorResponseException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Str;

/**
 * @template T
 */
class BaseApiClient
{
    protected Client $client;
    protected ?int $user_id = null;

    protected array $rawResponseMethods = [
        'exists',
        'delete',
    ];

    protected array $parentRelationData = [];
    protected array $whereConditions = [];

    protected array $whereHasRelation = [];
    protected array $loadingRelations = [];
    protected array $order = [];
    protected ?int $limit = null;

    protected string $apiPath;
    protected string $type;
    protected ?string $adapterClass;

    public function setUser(int $id)
    {
        $this->user_id = $id;
    }

    public function clearUser()
    {
        $this->user_id = null;
    }

    public function getParentData(): array
    {
        return [
            'parentRelationData' => $this->parentRelationData,
            'whereConditions' => $this->whereConditions,
            'whereHasRelation' => $this->whereHasRelation,
            'loadingRelations' => $this->loadingRelations,
            'order' => $this->order,
            'limit' => $this->limit,
        ];
    }

    public function __construct(string $type = '', ?string $adapterClass = null, ?string $apiPath = null, array $parentData = [])
    {
        $this->type = $type;
        $this->adapterClass = $adapterClass;
        $this->client = new Client([
            'base_uri' => config('domeny-sdk.base_uri'),
        ]);
        $this->apiPath = $apiPath ?? "api/v1/$this->type/query";

        foreach ($parentData as $key => $value) {
            $this->$key = $value;
        }
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
     * @param string $relationName
     * @param $callback
     * @param bool $not
     * @return $this
     */
    public function whereHas(string $relationName, $callback = null, bool $not = false): static
    {
        $this->whereHasRelation[] = [
            'relation' => $relationName,
            'callback' => $callback,
            'not' => $not,
        ];
        return $this;
    }

    public function when(bool $condition, callable $callback): static
    {
        if ($condition) {
            return call_user_func($callback, $this);
        }

        return $this;
    }

    /**
     * @param string $relationName
     * @param $callback
     * @return $this
     */
    public function whereDoesntHave(string $relationName, $callback = null): static
    {
        return $this->whereHas($relationName, $callback, true);
    }

    /**
     * @param string $relationName
     * @param $callback
     * @return $this
     */
    public function doesntHave(string $relationName, $callback = null): static
    {
        return $this->whereDoesntHave($relationName, $callback);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getWhereHas(): array
    {
        $whereHas = [];
        foreach ($this->whereHasRelation as $relation) {
            $relationName = $relation['relation'];

            if (! method_exists($this->adapterClass, $relationName)) {
                throw new Exception("Missing relation '$relationName' in '$this->adapterClass' adapter");
            }

            if (is_null($relation['callback']) || !is_callable($relation['callback'])) {
                $whereHas[] = [
                    'relation' => $relationName,
                    'not' => $relation['not'] ?? false,
                ];
                continue;
            }

            /**
             * @var BaseApiClient $apiClient
             */
            $apiClient = call_user_func($relation['callback'], (new $this->adapterClass)->{$relationName}());

            $whereHas[] = [
                'relation' => $relationName,
                'conditions' => $apiClient->getConditions(),
                'whereHas' => $apiClient->getWhereHas(),
                'not' => $relation['not'] ?? false,
            ];
        }
        return $whereHas;
    }

    protected function getHeaders(): array
    {
        return [
            'X-localization' => app()->getLocale(),
            'X-USER-ID' => $this->user_id,
        ];
    }

    /**
     * @param string $query_method
     * @param array $additional
     * @param bool $isSingleElement
     * @return T|T[]|LengthAwarePaginator<T>|bool
     * @throws GuzzleException
     * @throws ErrorResponseException
     * @throws Exception
     */
    private function runQuery(string $query_method, array $additional = [], bool $isSingleElement = false)
    {
        try {
            $conditions = $this->getConditions();
            $relations = $this->getRelations();
            $order = $this->getOrder();
            $limit = $this->getLimit();
            $whereHas = $this->getWhereHas();

            $response = $this->client->post($this->apiPath, [
                'json' => [
                    'conditions' => $conditions,
                    'query_method' => $query_method,
                    'relations' => $relations,
                    'whereHas' => $whereHas,
                    'order' => $order,
                    'limit' => $limit,
                    ...$additional
                ],
                'headers' => $this->getHeaders()
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
                ],
                'headers' => $this->getHeaders()
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
     * @param string $query_method
     * @param array $data
     * @param array $additional
     * @param bool $rawResult
     * @return T|null|mixed
     * @throws ErrorResponseException
     * @throws GuzzleException
     */
    public function runMultiChangeData(string $query_method, array $data, array $additional, bool $rawResult = false)
    {
        // merge parentRelationData to each row
        $data = array_map(
            fn ($item) => array_merge($this->parentRelationData, $item),
            $data
        );
        $conditions = $this->getConditions();

        try {
            $response = $this->client->post($this->apiPath, [
                'json' => [
                    'query_method' => $query_method,
                    'conditions' => $conditions,
                    'additional' => $additional,
                    'data' => $data,
                ],
                'headers' => $this->getHeaders()
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
     * @param array|string $uniqueBy
     * @return int
     * @throws ErrorResponseException
     * @throws GuzzleException
     */
    public function upsert(array $data, array|string $uniqueBy): int
    {
        return (int) $this->runMultiChangeData('upsert', $data, additional: ['uniqueBy' => $uniqueBy], rawResult:true);
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
     * @return T
     * @throws ErrorResponseException
     * @throws GuzzleException
     */
    public function find($id = null): mixed
    {
        if (func_num_args() > 0) {
            $this->where('id', $id);
        }
        return $this->first();
    }

    /**
     * @return T
     * @throws ErrorResponseException
     * @throws GuzzleException
     * @throws ModelNotFoundException
     */
    public function firstOrFail(): mixed
    {
        $item = $this->first();

        if (is_null($item)) {
            throw (new ModelNotFoundException)->setModel($this->adapterClass);
        }

        return $item;
    }

    /**
     * @return T
     * @throws ErrorResponseException
     * @throws GuzzleException
     * @throws ModelNotFoundException
     */
    public function findOrFail($id = null): mixed
    {
        if (func_num_args() > 0) {
            $this->where('id', $id);
        }

        return $this->firstOrFail();
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

                $query = call_user_func($closure, new static);

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
        // If it is simple where
        if ($this->isSimpleCondition($whereCondition)) {
            return $this->parseConditionValues($whereCondition);
        }

        // If it is a callback for example $q->where(fn($q) => $q->where(...)->orWhere(...));
        if (is_callable($whereCondition['column'])) {
            // We set operator as custom so api will process it as not a simple where.
            // Then we try to parse conditions from a new (new static) query as if it was really a new query.
            // This way we will recursively get parsed array of conditions.
            return [
                'operator' => 'custom',
                'value' => call_user_func($whereCondition['column'], ((new static())))->getConditions(),
                'boolean' => $whereCondition['boolean'],
            ];
        }

        // If we are not sure what the condition is, lets just try to parse it for now.
        return $this->parseConditionValues($whereCondition);
    }

    protected function parseConditionValues(array $whereCondition): array
    {
        if ($this->isRawValue($whereCondition['column'] ?? null)) {
            $whereCondition['raw_column'] = true;
            $whereCondition['column'] = $whereCondition['column']->value;
        }
        if ($this->isRawValue($whereCondition['value'] ?? null)) {
            $whereCondition['raw_value'] = true;
            $whereCondition['value'] = $whereCondition['value']->value;
        }

        return $whereCondition;
    }

    protected function isRawValue(mixed $column): bool
    {
        return $column instanceof DatabaseRawValue;
    }

    /**
     * Checks if where-condition is a simple where without callbacks.
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
        [$operator, $value] = $this->parseOperators($operator, $value, func_num_args());

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
     * @param $operator
     * @param $value
     * @param $numArgs
     * @return array{mixed, mixed}
     */
    protected function parseOperators($operator, $value, $numArgs): array
    {
        if (is_null($value) && !empty($operator) && $numArgs === 2) {
            $value = $operator;
            $operator = '=';
        }
        return [$operator, $value];
    }

    /**
     * @param string|DatabaseRawValue $query
     * @return static
     */
    public function whereRaw(string|DatabaseRawValue $query): static
    {
        if (is_string($query)) {
            $query = DB::raw($query);
        }

        return $this->where($query);
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
        [$operator, $value] = $this->parseOperators($operator, $value, func_num_args());
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
        [$operator, $value] = $this->parseOperators($operator, $value, func_num_args());
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
        [$operator, $value] = $this->parseOperators($operator, $value, func_num_args());
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
     * @return $this
     */
    public function inRandomOrder(): static
    {
        $this->order[] = [
            'type' => 'random'
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

    public function __call(string $name, array $arguments)
    {
        if (method_exists($this, $name)) {
            return $this->$name(...$arguments);
        }

        $scopeMethodName = Str::of($name)->camel()->ucfirst()->prepend('scope')->toString();

        if (method_exists($this->adapterClass, $scopeMethodName)) {
            return ($this->adapterClass::fromArray([]))->{$scopeMethodName}($this, ...$arguments) ?? $this;
        }

        throw new Exception('Method ' . $name . ' does not exist in ' . static::class);
    }
}
