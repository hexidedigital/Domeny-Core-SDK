<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\Adapters;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Hexidedigital\DomenyCoreSdk\Classes\Adapters\Users\UserModelAdapter;
use Hexidedigital\DomenyCoreSdk\Classes\ApiClients\BaseApiClient;
use Hexidedigital\DomenyCoreSdk\Classes\ApiClients\BelongsTo;
use Hexidedigital\DomenyCoreSdk\Classes\ApiClients\BelongsToMany;
use Hexidedigital\DomenyCoreSdk\Classes\ApiClients\HasMany;
use Hexidedigital\DomenyCoreSdk\Classes\ApiClients\HasOne;
use Hexidedigital\DomenyCoreSdk\Exceptions\ErrorResponseException;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use Str;

/**
 * @mixin BaseApiClient
 */
abstract class BaseAdapter
{
    protected array $loadedRelations = [];

    protected static array $relationTypes = [
        BelongsTo::class,
        BelongsToMany::class,
        HasMany::class,
        HasOne::class,
    ];

    /**
     * @param array $data
     * @return static
     * @throws ErrorResponseException
     * @throws GuzzleException
     */
    public function update(array $data): static
    {
        $newModel = $this->getApi()->updateSingle($this->id, $data);
        foreach (array_keys($data) as $field) {
            $this->{$field} = $newModel->{$field};
        }
        return $this;
    }

    public function relationLoaded(string $relation): bool
    {
        return in_array($relation, $this->loadedRelations);
    }


    public static function getApi(?string $apiClientClass = null): BaseApiClient
    {
        $apiClientClass ??= BaseApiClient::class;

        $type = static::getApiType();

        $class = static::class;

        return new $apiClientClass($type, $class);
    }

    public static function getApiType(): string
    {
        return Str::of(static::class)->classBasename()->snake()->toString();
    }

    public function delete(): bool
    {
        return static::getApi()->where('id', $this->id)->delete();
    }

    public function markLoadedRelations(array $relations): static
    {
        foreach ($relations as $relation) {
            $relationName = $relation['relation'] ?? null;
            if (empty($relationName)) {
                continue;
            }
            $this->loadedRelations[$relationName] = $relationName;
            if (!empty($relation['relations'])) {
                if (is_array($this->{$relationName})) {
                    $this->{$relationName} = \Arr::map($this->{$relationName}, fn ($item) => $item?->markLoadedRelations($relation['relations']));
                } else {
                    $this->{$relationName}?->markLoadedRelations($relation['relations']);
                }
            }
        }

        return $this;
    }

    /**
     * @throws ErrorResponseException
     * @throws GuzzleException
     */
    public function load(array|string $data): static
    {
        $data = is_string($data) ? [$data] : $data;
        $newModel = static::getApi()->with($data)->where('id', $this->id)->first();

        foreach ($data as $relationName => $relationData) {
            if (is_numeric($relationName) && is_string($relationData)) {
                $relationName = $relationData;
            }
            $this->{$relationName} = $newModel->{$relationName};
            $this->loadedRelations[$relationName] = $relationName;
        }
        return $this;
    }
    /**
     * @throws Exception
     */
    public static function fromArray(array $data, array $relations = []): static
    {
        $reflect = new ReflectionClass(static::class);
        $selfReflect = new ReflectionClass(self::class);
        $object = new static;

        // Initialize properties
        foreach ($reflect->getProperties() as $property) {
            if ($selfReflect->hasProperty($property->name)) {
                continue;
            }
            $object->{$property->name} = static::parseProperty(
                $data[$property->name] ?? null,
                $property->getType()->getName()
            );
        }

        // Initialize loaded relations
        foreach ($reflect->getMethods() as $method) {
            $returnType = (string) $method->getReturnType();
            if (
                !$reflect->hasProperty($method->name)
                && $method->hasReturnType()
                && in_array($returnType, self::$relationTypes)
                && $method->name != $returnType::METHOD_NAME
            ) {
                $relation = $object->{$method->name}();
                $adapter = $relation->adapterClass ?? null;

                // region Set count relations (withCount method return)
                $countName = Str::snake($method->name) . '_count';
                if (key_exists($countName, $data)) {
                    $object->{$countName} = $data[$countName];
                }
                // endregion

                // region Set exists relations (withExists method return)
                $existsName = Str::snake($method->name) . '_exists';
                if (key_exists($existsName, $data)) {
                    $object->{$existsName} = (bool) $data[$existsName];
                }
                // endregion

                if (! key_exists(Str::snake($method->name), $data)) {
                    continue;
                }
                $value = $data[Str::snake($method->name)];

                if (method_exists($relation, 'isMultiple') && $relation->isMultiple()) {
                    $value = $value[0] ?? null;
                }

                if (empty($adapter)) {
                    continue;
                }

                $pivotAdapter = null;
                if (method_exists($relation, 'getPivotColumns') && !empty($relation->getPivotColumns())) {
                    $pivotAdapter = $relation->getRelationAdapterClass();
                }

                $object->{$method->name} = static::parseRelation(
                    $value,
                    $returnType,
                    $adapter,
                    $pivotAdapter
                );
            }
        }

        return $object->markLoadedRelations($relations);
    }

    public function newCollection(array $models = [])
    {
        return collect($models);
    }
//    /**
//     * @throws Exception
//     */
//    public static function fromArray(array $data, array $relations = []): static
//    {
//        $reflect = new ReflectionClass(static::class);
//        $classData = [];
//        foreach ($reflect->getProperties() as $property) {
//            if ($property->isProtected() || $property->isPrivate()) {
//                continue;
//            }
//            $classData[$property->name] = static::parseProperty(
//                $data[$property->name] ?? null,
//                $property->getType()->getName()
//            );
//        }
//
//        return (new static(...$classData))->markLoadedRelations($relations);
//    }

    /**
     * @throws Exception
     */
    private static function parseProperty(mixed $value, string $type): mixed
    {
        if (empty($value)) {
            return null;
        }
        return match ($type) {
            'int'       => (int)$value,
            'string'    => (string)$value,
            'bool'      => (bool)$value,
            'array'     => (array)$value,

            Carbon::class       => Carbon::parse($value),

            default => static::tryToParseClassProperty($value, $type),
        };
    }
    /**
     * @throws Exception
     */
    private static function parseRelation(mixed $value, string $relationType, string $adapterClass, ?string $pivotAdapterClass = null): mixed
    {
        if (empty($value)) {
            return null;
        }

        if (($relationType::IS_ARRAY ?? false) && is_array($value)) {
            return \Arr::map($value, fn ($item) => static::tryToParseClassProperty($item, $adapterClass, $pivotAdapterClass));
        }

        return static::tryToParseClassProperty($value, $adapterClass, $pivotAdapterClass);
    }

    /**
     * @throws Exception
     */
    protected static function tryToParseClassProperty($value, $type, $pivotAdapterClass = null): mixed
    {
        if (class_exists($type)) {
            if (method_exists($type, 'fromArray') ?? is_array($value)) {
                return static::setPivot($type::fromArray($value), $value['pivot'] ?? [], $pivotAdapterClass);
            } elseif (method_exists($type, 'from')) {
                return $type::from($value);
            }
        }

        if (config('domeny-sdk.throw_exception_for_adapters')) {
            throw new Exception("Undefined property type: '$type'");
        }

        return null;
    }

    protected static function setPivot(mixed $object, $pivotData, $pivotAdapterClass): mixed
    {
        if (empty($pivotAdapterClass)) {
            return $object;
        }

        if (! class_exists($pivotAdapterClass) || !method_exists($pivotAdapterClass, 'fromArray')) {
            return $object;
        }

        $object->pivot = $pivotAdapterClass::fromArray($pivotData);

        return $object;
    }

    /**
     * @param string $relationClass
     * @param string|null $foreign_key
     * @param string $key
     * @return HasOne
     */
    protected function hasOne(string $relationClass, ?string $foreign_key = null, string $key = 'id'): HasOne
    {
        if (empty($foreign_key)) {
            $foreign_key = $this->guessForeignKey(static::class);
        }

        if (! class_exists($relationClass)) {
            throw new Exception("Class '$relationClass' does not exist");
        }

        if (! method_exists($relationClass, 'getApi')) {
            throw new Exception("Class '$relationClass' does not have a getApi method");
        }
        $api = $relationClass::getApi(HasOne::class);
        $parentKey = $this->{$key} ?? null;
        if (!empty($parentKey)) {
            $api = $api->setParentRelationData([$foreign_key => $this->{$key}])
                ->where($foreign_key, $this->{$key})
                ->limit(1);
        }

        return $api;
    }

    /**
     * @param string $relationClass
     * @param string|null $foreign_key
     * @param string $key
     * @return HasMany
     */
    public function hasMany(string $relationClass, ?string $foreign_key = null, string $key = 'id'): HasMany
    {
        if (empty($foreign_key)) {
            $foreign_key = $this->guessForeignKey(static::class);
        }

        if (! class_exists($relationClass)) {
            throw new Exception("Class '$relationClass' does not exist");
        }

        if (! method_exists($relationClass, 'getApi')) {
            throw new Exception("Class '$relationClass' does not have a getApi method");
        }
        $api = $relationClass::getApi(HasMany::class);
        $parentKey = $this->{$key} ?? null;
        if (!empty($parentKey)) {
            $api = $api->setParentRelationData([$foreign_key => $this->{$key}])
                ->where($foreign_key, $this->{$key});
        }

        return $api;
    }

    /**
     * @param string $relationClass
     * @param string|null $table
     * @param string|null $foreignPivotKey
     * @param string|null $relatedPivotKey
     * @param string $parentKey
     * @param string $relatedKey
     * @return BelongsToMany
     */
    public function belongsToMany(
        string $relationClass,
        ?string $table = null,
        ?string $foreignPivotKey = null,
        ?string $relatedPivotKey = null,
        string $parentKey = 'id',
        string $relatedKey = 'id',
    ): BelongsToMany {
        if (! class_exists($relationClass)) {
            throw new Exception("Class '$relationClass' does not exist");
        }

        if (! method_exists($relationClass, 'getApi')) {
            throw new Exception("Class '$relationClass' does not have a getApi method");
        }


        if (empty($table)) {
            $table = $this->guessPivotTable(static::class, $relationClass);
        }

        if (empty($foreignPivotKey)) {
            $foreignPivotKey = $this->guessForeignKey(static::class);
        }

        if (empty($relatedPivotKey)) {
            $relatedPivotKey = $this->guessForeignKey($relationClass);
        }

        $parentKeyValue = $this->{$parentKey} ?? null;
        $relatedTable = $this->guessTableNameFromClass($relationClass);

        $api = $relationClass::getApi(BelongsToMany::class);

        $api->setParentItem($this, $this->getCallerFunction());


        if (!empty($parentKeyValue)) {
            $api->whereRaw("EXISTS(
                            select * from `$table`
                            where `$table`.`$foreignPivotKey` = '$parentKeyValue'
                            and `$table`.`$relatedPivotKey` = `$relatedTable`.`$relatedKey`
                        )");
        }

        return $api;
    }

    /**
     * This function returns parent method name
     * For example if I called domains() relation, which then called belongsToMany() method
     * If we will call this method inside belongsToMany, it will return 'domains' as string.
     * @return string|null
     */
    protected function getCallerFunction(): ?string
    {
        // Get all stack trace, but limit it to 3 records, and ignore arguments
        $stackTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, limit: 3);
        // Return third element function name
        return $stackTrace[2]['function'] ?? null;
    }

    /**
     * @param string $relationClass
     * @param string|null $foreign_key
     * @param string $key
     * @return BelongsTo
     */
    protected function belongsTo(string $relationClass, ?string $foreign_key = null, string $key = 'id'): BelongsTo
    {
        if (empty($foreign_key)) {
            $foreign_key = $this->guessForeignKey($relationClass);
        }

        if (! class_exists($relationClass)) {
            throw new Exception("Class '$relationClass' does not exist");
        }

        if (! method_exists($relationClass, 'getApi')) {
            throw new Exception("Class '$relationClass' does not have a getApi method");
        }

        $api = $relationClass::getApi(BelongsTo::class);
        $parentKey = $this->{$key} ?? null;
        if (!empty($parentKey)) {
            $api = $api->setParentRelationData([$key => $this->{$foreign_key}])->where($key, $this->{$foreign_key});
        }

        return $api;
    }

    protected function guessPivotTable(string $fromClass, string $toClass): string
    {
        $from = Str::of($fromClass)->classBasename()->snake()->replace('_model_adapter', '')->toString();
        $to = Str::of($toClass)->classBasename()->snake()->replace('_model_adapter', '')->toString();

        return sprintf('%s_%s', $from, $to);
    }

    protected function guessTableNameFromClass(string $class): string
    {
        return Str::of($class)
            ->classBasename()
            ->snake()
            ->replace('_model_adapter', '')
            ->plural()
            ->toString();
    }

    protected function guessForeignKey(string $class): string
    {
        return Str::of($class)->classBasename()->snake()->replace('_model_adapter', '')->append('_id')->toString();
    }

    public function __get(string $name)
    {
        $reflection = new ReflectionClass($this);

        if (
            $reflection->hasMethod($name)
            && $reflection->getMethod($name)->hasReturnType()
            && in_array($reflection->getMethod($name)->getReturnType(), static::$relationTypes)
            && !in_array($name, $this->loadedRelations)
            && config('domeny-sdk.lazy_loading')
        ) {
            return $this->load($name)->$name;
        }

        return $this->$name;
    }

    public static function __callStatic(string $name, array $arguments)
    {
        if (method_exists(static::class, $name)) {
            return static::{$name}(...$arguments);
        }

        return static::getApi()->{$name}(...$arguments);
    }
}
