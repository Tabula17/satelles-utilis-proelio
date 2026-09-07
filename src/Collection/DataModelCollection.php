<?php

namespace Tabula17\Satelles\Utilis\Collection;

use Tabula17\Satelles\Utilis\Data\ModelDescriptor;
use Tabula17\Satelles\Utilis\Exception\BadMethodCallException;
use Tabula17\Satelles\Utilis\Exception\InvalidArgumentException;
use Tabula17\Satelles\Utilis\Exception\UnexpectedValueException;

/**
 * Represents a collection that enforces type constraints, specifically for ModelDescriptor objects.
 *
 * Extends TypedCollection to provide additional functionality and specific restrictions on methods.
 */
class DataModelCollection extends TypedCollection
{

    protected static function getType(): string
    {
        return ModelDescriptor::class;
    }

    /**
     * @throws BadMethodCallException
     */
    public function add(mixed $value): void
    {
        throw new BadMethodCallException('Method DataModelCollection::add is disabled. Use DataModelCollection::set instead.');
    }
    /**
     * @throws BadMethodCallException
     */
    public function addIfNotExist(mixed $value, bool $strict = true): bool
    {
        throw new BadMethodCallException('Method DataModelCollection::addIfNotExist is disabled. Use DataModelCollection::set instead.');
    }

    /**
     * @throws BadMethodCallException
     * @throws UnexpectedValueException
     * @throws InvalidArgumentException
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if( $offset === null ) {
            throw new BadMethodCallException('Argument $offset cannot be null in DataModelCollection::offsetSet.');
        }
        $this->set($offset, $value);
    }
}