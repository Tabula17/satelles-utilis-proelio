<?php

namespace Tabula17\Satelles\Utilis\Collection;

use ArrayIterator;
use IteratorAggregate;
use JsonException;
use Traversable;

/**
 * An abstract base class representing a generic collection.
 * Provides common collection functionality and implements IteratorAggregate
 * to allow iteration over contained elements.
 */
abstract class GenericCollection implements IteratorAggregate
{
    protected array $values;

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->values);
    }

    /**
     * @return array
     * @throws JsonException
     */
    public function toArray(): array
    {
        return json_decode(json_encode($this->values, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->values);
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->values);
    }

    /**
     * Finds and returns the first element in the collection that satisfies the given callback.
     *
     * @param callable $callback A callback function used to determine if an element matches the criteria.
     * @return mixed Returns the first matching element if found, or null if no matching element is found.
     */
    public function find(callable $callback): mixed
    {
        return array_values(array_filter($this->values, $callback))[0] ?? null;
    }

    /**
     * @return mixed
     */
    public function first(): mixed
    {
        return reset($this->values);
    }

    /**
     * @return mixed
     */
    public function last(): mixed
    {
        return end($this->values);
    }

    /**
     * Serializes the object to an array.
     * @return array
     */
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

    /**
     * @param array $data The data to unserialize and populate the object with.
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->values = $data;
    }
}