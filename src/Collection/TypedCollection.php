<?php

namespace Tabula17\Satelles\Utilis\Collection;

use Tabula17\Satelles\Utilis\Collection\GenericCollection;
use Tabula17\Satelles\Utilis\Exception\UnexpectedValueException;

/**
 * Represents a typed collection that enforces all items to be instances of a specified type.
 *
 * This class extends the functionality of GenericCollection while allowing strict type consistency
 * for the contained elements. The type is defined at runtime and validated upon creation of the collection,
 * ensuring that all elements conform to the defined type.
 */
abstract class TypedCollection extends GenericCollection
{

    /**
     * @throws UnexpectedValueException
     */
    public function __construct(...$values)
    {
        if(empty(static::getType())) {
            throw new UnexpectedValueException('Type must be defined for TypedCollection');
        }
        $type = static::getType();
        array_walk($values, static fn($value) => $value instanceof $type ?: new $type($value));
        $this->values = $values;
    }

    abstract protected static function getType(): string;
    /**
     * @throws UnexpectedValueException
     */
    public static function fromArray(array $config, ?string $type = null): self
    {
        $class = $type ?? static::getType();
        return new static(...array_map(static fn($item) => $item instanceof $class ? $item : new $class($item), $config));
    }
}