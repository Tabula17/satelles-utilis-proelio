<?php

namespace Tabula17\Satelles\Utilis\Collection;

use ArrayIterator;
use Countable;
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
abstract class GenericCollection implements IteratorAggregate, ArrayAccess, JsonSerializable, Countable
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
        return array_find($this->values, static fn($value, $key) => $callback($value, $key));
    }

    public function findKey(callable $callback): mixed
    {
        return array_find_key($this->values, static fn($value, $key) => $callback($value, $key));
    }

    public function filter(callable $callback): static
    {
        $filtered = array_filter($this->values, $callback);
        return new static(...$filtered);
    }

    public function filterKeys(callable $callback): static
    {
        $filtered = array_filter($this->values, $callback, ARRAY_FILTER_USE_KEY);
        return new static(...$filtered);
    }

    public function filterKeyOrValue(callable $callback): static
    {
        $filtered = array_filter($this->values, $callback, ARRAY_FILTER_USE_BOTH);
        return new static(...$filtered);
    }

    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->values, $callback, $initial);
    }

    public function map(callable $callback): array
    {
        return array_map($callback, $this->values);
    }

    public function some(callable $callback): bool
    {
        return array_any($this->values, static fn($value, $key) => $callback($value, $key));
    }

    public function every(callable $callback): bool
    {
        return array_all($this->values, static fn($value, $key) => $callback($value, $key));
    }

    public function first(): mixed
    {
        return $this->values[array_key_first($this->values)] ?? null;
    }

    public function last(): mixed
    {
        return $this->values[array_key_last($this->values)] ?? null;
    }

    public function pop(): mixed
    {
        return array_pop($this->values);
    }

    public function push(...$values): int
    {
        return array_push($this->values, ...$values);
    }

    public function shift(): mixed
    {
        return array_shift($this->values);
    }

    public function unshift(...$values): int
    {
        return array_unshift($this->values, ...$values);
    }

    public function remove(mixed $value): void
    {
        $this->values = array_filter($this->values, static fn($item) => $item !== $value);
    }

    public function removeAt(int $index): void
    {
        unset($this->values[$index]);
    }

    public function clear(): void
    {
        $this->values = [];
    }

    public function set(mixed $key, mixed $value): void
    {
        $this->values[$key] = $value;
    }

    public function add(mixed $value): void
    {
        $this->values[] = $value;
    }

    public function addIfNotExist(mixed $value): void
    {
        if (!in_array($value, $this->values, true)) {
            $this->values[] = $value;
        }
    }

    public function contains(mixed $value): bool
    {
        return in_array($value, $this->values, true);
    }

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

    /**
     * Merges the current collection with one or more provided collections.
     *
     * @param bool $strict Determines whether to enforce that all provided collections must be instances of the same class as the current object.
     * @param self ...$collections One or more collections to merge with the current collection.
     * @return void
     */
    public function merge(bool $strict = false, self ...$collections): void
    {
        $values = $this->values;
        foreach ($collections as $collection) {
            if ($strict && !($collection instanceof static)) {
                continue;
            }
            $values = $collection->toArray();
        }
        $this->values = array_merge(...$values);
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

    public function __clone()
    {
        $this->values = array_map(fn($value) => clone $value, $this->values);
    }
}