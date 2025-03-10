<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\Adapters;

use Carbon\Carbon;
use Exception;
use Hexidedigital\DomenyCoreSdk\Enums\DomainStatus;
use ReflectionClass;

abstract class BaseAdapter
{
    /**
     * @throws Exception
     */
    public static function fromArray(array $data): static
    {
        $reflect = new ReflectionClass(static::class);

        $classData = [];
        foreach ($reflect->getProperties() as $property) {
            $classData[$property->name] = static::parseProperty(
                $data[$property->name] ?? null,
                $property->getType()->getName()
            );
        }

        return new static(...$classData);
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
}