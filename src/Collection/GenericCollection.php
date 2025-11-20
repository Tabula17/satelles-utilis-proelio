<?php

namespace Tabula17\Satelles\Utilis\Collection;

use ArrayIterator;
use IteratorAggregate;
use JsonException;
use Traversable;
use ArrayAccess;
use JsonSerializable;

/**
 * Abstract class representing a generic collection of items.
 * This class provides common methods to manipulate and access
 * the data stored within the collection. The class implements
 * IteratorAggregate for traversable behavior, ArrayAccess for array-like
 * manipulation, and JsonSerializable for JSON serialization.
 */
abstract class GenericCollection implements IteratorAggregate, ArrayAccess, JsonSerializable
{
    protected array $values = [];

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->values);
    }

    public function toArray(): array
    {
        $result = [];
        foreach ($this->values as $key => $value) {
            if ($value instanceof JsonSerializable) {
                $result[$key] = $value->jsonSerialize();
            } elseif (is_object($value) && method_exists($value, 'toArray')) {
                $result[$key] = $value->toArray();
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function count(): int
    {
        return count($this->values);
    }

    public function isEmpty(): bool
    {
        return empty($this->values);
    }

    public function find(callable $callback): mixed
    {
        return array_find($this->values, static fn($value) => $callback($value));
    }

    public function filter(callable $callback): static
    {
        $filtered = array_filter($this->values, $callback);
        return new static(...$filtered);
    }

    public function map(callable $callback): array
    {
        return array_map($callback, $this->values);
    }

    public function some(callable $callback): bool
    {
        return array_any($this->values, static fn($value) => $callback($value));
    }

    public function every(callable $callback): bool
    {
        return array_all($this->values, static fn($value) => $callback($value));
    }

    public function first(): mixed
    {
        return $this->values[array_key_first($this->values)] ?? null;
    }

    public function last(): mixed
    {
        return $this->values[array_key_last($this->values)] ?? null;
    }

    // ArrayAccess methods
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->values[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->values[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->values[] = $value;
        } else {
            $this->values[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->values[$offset]);
    }

    public function __serialize(): array
    {
        $definedVars = $this->values;
        $data = [];
        foreach ($definedVars as $property => $value) {
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
        $this->values = $data;
    }
}