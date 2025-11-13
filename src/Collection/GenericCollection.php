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
     * @throws JsonException
     */
    public function toArray(): array
    {
        return json_decode(json_encode($this->values), true, 512, JSON_THROW_ON_ERROR);
    }

    public function count(): int
    {
        return count($this->values);
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function __unserialize(array $data): void
    {
        $this->values = $data;
    }
}