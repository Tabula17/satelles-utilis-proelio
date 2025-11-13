<?php

namespace Tabula17\Satelles\Utilis\Config;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use JsonSerializable;
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
        if (is_array($values)) {
            $this->_load($values);
        }
    }

    public function set($property, $value): void
    {
        if (property_exists($this, $property)) {
            $this->$property = $value;
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
        return new ArrayIterator($this);
    }

    public function _load(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __serialize(): array
    {
        $definedVars = get_class_vars($this::class);
        $data = [];
        foreach (get_object_vars($this) as $property => $value) {
            if (!property_exists($this, $property) || (array_key_exists($property, $definedVars) && $value === $definedVars[$property])) {
                continue;
            }
            if (is_object($value) && method_exists($value, '__serialize')) {
                $data[$property] = $value->__serialize();
            } else {
                $data[$property] = $value;
            }
        }
        return $data;
    }

    public function __unserialize(array $data): void
    {
        $this->_load($data);
    }

}