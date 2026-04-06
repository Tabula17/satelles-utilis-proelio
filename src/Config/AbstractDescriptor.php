<?php

namespace Tabula17\Satelles\Utilis\Config;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;
use ReflectionUnionType;
use Traversable;

/**
 * AbstractDescriptor provides a base implementation for an object that supports
 * ArrayAccess, IteratorAggregate, and JsonSerializable interfaces. It offers a
 * standard way to manage properties, serialization, and data manipulation.
 */
abstract class AbstractDescriptor implements ArrayAccess, IteratorAggregate, JsonSerializable
{
    public function __construct(?array $values = [])
    {
        $this->loadProperties($values);
    }

    public function set(string $property, mixed $value): void
    {
        if (property_exists($this, $property)) {
            $setterMethod = 'set' . ucfirst($property);
            if (method_exists($this, $setterMethod)) {
                $this->$setterMethod($value);
            } else {
                $this->$property = $value;
            }
        }
    }

    public function get(string $property): mixed
    {
        return $this->$property;
    }

    public function offsetExists(mixed $offset): bool
    {
        return property_exists($this, $offset) && isset($this->$offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {

        $this->set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        if ($this->offsetExists($offset)) {
            unset($this->$offset);
        }
    }

    public function toArray(): array
    {
        $data = [];
        foreach (get_object_vars($this) as $property => $value) {
            if (is_object($value) && method_exists($value, 'toArray')) {
                $data[$property] = $value->toArray();
            } else {
                $data[$property] = $value;
            }
        }
        return $data;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->toArray());
    }

    public function loadProperties(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Devuelve un array con los valores de las propiedades inicializadas
     * IMPORTANTE: Al usar ReflectionClass la performance puede verse afectada
     * @return array
     */
    public function getInitialized(): array
    {
        $reflection = new ReflectionClass($this);
        $initializedProperties = array_filter(
            $reflection->getProperties(),
            fn($property) => $property->isInitialized($this)
        );

        $propertyNames = array_column($initializedProperties, 'name');

        return array_intersect_key(
            $this->toArray(),
            array_flip($propertyNames)
        );
    }

    /**
     * Serializa un valor recursivamente
     */
    private function serializeValue(mixed $value): mixed
    {
        if (is_object($value)) {
            if (method_exists($value, '__serialize')) {
                return $value->__serialize();
            }

            if ($value instanceof \JsonSerializable) {
                return $value->jsonSerialize();
            }

            if (method_exists($value, 'toArray')) {
                return $value->toArray();
            }
        } elseif (is_array($value)) {
            return array_map([$this, 'serializeValue'], $value);
        }

        return $value;
    }

    public static function getModelInfo(): array
    {
        $reflection = new ReflectionClass(static::class);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        $model = [
            'class' => static::class,
            'constructor' => [],
            'properties' => [],

        ];
        foreach ($reflection->getConstructor()?->getParameters() ?? [] as $parameter) {
            $model['constructor'][$parameter->getName()] =
                [
                    'type' => $parameter->getType()?->getName() ?? 'mixed',
                ];
        }
        foreach ($properties as $property) {

            $model['properties'][$property->getName()] =
                [
                    'type' => $property->getType()?->getName() ?? 'mixed',
                ];
            if (is_a($property->getType()?->getName() ?? '', AbstractDescriptor::class, true)) {
                $child = ($property->getType()->getName());
                $model['properties'][$property->getName()]['model'] = $child::getModelInfo();
            }
        }
        return $model;

    }

    public static function getModel(): array
    {
        $response = [];
        $reflection = new ReflectionClass(static::class);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        foreach ($properties as $property) {
            $prpopType = $property->getType();
            if ($prpopType instanceof ReflectionUnionType) {
                $type = [];
                foreach ($prpopType->getTypes() as $type) {
                    $type[] = $type->getName();
                }
                $type = implode('|', $type);
            } else {
                $type = $prpopType ?? 'mixed';
            }

            if (is_a($type, AbstractDescriptor::class, true)) {
                $response[$property->getName()] = $type::getModel();
            } else {

                $response[$property->getName()] = $type;
            }
        }
        return $response;
    }

    public function __serialize(): array
    {
        $defaultValues = get_class_vars(static::class);
        $currentValues = get_object_vars($this);
        $data = [];

        foreach ($currentValues as $property => $value) {
            if (!property_exists($this, $property)) {
                continue;
            }

            $defaultValue = $defaultValues[$property] ?? null;

            // Serializar solo si el valor es diferente al default
            if ($value !== $defaultValue) {
                $data[$property] = $this->serializeValue($value);
            }
        }
        return $data;
    }

    public function __unserialize(array $data): void
    {
        $this->loadProperties($data);
    }

    public function __get(string $name)
    {
        return $this->get($name);
    }

    public function __set(string $name, $value): void
    {
        $this->set($name, $value);
    }

    public function __isset(string $name): bool
    {
        return property_exists($this, $name) && isset($this->$name);
    }

    public function __unset(string $name): void
    {
        unset($this->$name);
    }

    public function __clone()
    {
        $this->loadProperties($this->__serialize());
    }

}