<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\Adapters;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Hexidedigital\DomenyCoreSdk\Classes\Adapters\Users\UserModelAdapter;
use Hexidedigital\DomenyCoreSdk\Classes\ApiClients\BaseApiClient;
use Hexidedigital\DomenyCoreSdk\Exceptions\ErrorResponseException;
use ReflectionClass;
use Str;

/**
 * @mixin BaseApiClient
 */
abstract class BaseAdapter
{
    protected array $loadedRelations = [];

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


    public static function getApi(): BaseApiClient
    {
        $type = Str::of(static::class)->classBasename()->snake()->toString();

        $class = static::class;

        return new BaseApiClient($type, $class);
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
                $this->{$relationName}?->markLoadedRelations($relation['relations']);
            }
        }
        return $this;
    }

    /**
     * @throws ErrorResponseException
     * @throws GuzzleException
     */
    public function load(array $data): static
    {
        \Log::debug(static::class);
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

        $classData = [];
        foreach ($reflect->getProperties() as $property) {
            if ($property->isProtected() || $property->isPrivate()) {
                continue;
            }
            $classData[$property->name] = static::parseProperty(
                $data[$property->name] ?? null,
                $property->getType()->getName()
            );
        }

        return (new static(...$classData))->markLoadedRelations($relations);
    }

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
    protected static function tryToParseClassProperty($value, $type): mixed
    {
        if (class_exists($type)) {
            if (method_exists($type, 'fromArray') ?? is_array($value)) {
                return $type::fromArray($value);
            } elseif (method_exists($type, 'from')) {
                return $type::from($value);
            }
        }

        if (config('domeny-sdk.throw_exception_for_adapters')) {
            throw new Exception("Undefined property type: '$type'");
        }

        return null;
    }

    /**
     * @param string $relationClass
     * @param string|null $foreign_key
     * @param string $key
     * @return BaseApiClient
     */
    protected function hasOne(string $relationClass, ?string $foreign_key = null, string $key = 'id'): BaseApiClient
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
        $api = $relationClass::getApi();
        if (!empty($this->{$key})) {
            $api = $api->setParentRelationData([$foreign_key => $this->{$key}])->where($foreign_key, $this->{$key});
        }

        return $api;
    }

    public function belongsToMany(
        string $relationClass,
        ?string $table = null,
        ?string $foreignPivotKey = null,
        ?string $relatedPivotKey = null,
        string $parentKey = 'id',
        string $relatedKey = 'id',
    ): BaseApiClient {
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

        $parentKeyValue = $this->{$parentKey};
        $relatedTable = $this->guessTableNameFromClass($relationClass);

        $api = $relationClass::getApi();

        $api->whereRaw("EXISTS(
                            select * from `$table`
                            where `$table`.`$foreignPivotKey` = '$parentKeyValue'
                            and `$table`.`$relatedPivotKey` = `$relatedTable`.`$relatedKey`
                        )");

        return $api;
    }

    protected function belongsTo(string $relationClass, ?string $foreign_key = null, string $key = 'id'): BaseApiClient
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

        $api = $relationClass::getApi();
        if (!empty($this->{$key})) {
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


    public static function __callStatic(string $name, array $arguments)
    {
        if (method_exists(static::class, $name)) {
            return static::{$name}(...$arguments);
        }

        return static::getApi()->{$name}(...$arguments);
    }
}
