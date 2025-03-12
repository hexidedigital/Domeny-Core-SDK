<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\Adapters;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Hexidedigital\DomenyCoreSdk\Classes\ApiClients\BaseApiClient;
use Hexidedigital\DomenyCoreSdk\Exceptions\ErrorResponseException;
use ReflectionClass;

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


    protected static function getApi(): BaseApiClient
    {
        throw new Exception('getApi method not implemented in ' . static::class);
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

        return $relationClass::getApi()->setParentRelationData([$foreign_key => $this->{$key}])->where($foreign_key, $this->{$key});
    }

    protected function guessForeignKey(string $class): string
    {
        $class = \Str::snake($class);
        return str_replace('model_adapter', '', $class) . '_id';
    }
}
