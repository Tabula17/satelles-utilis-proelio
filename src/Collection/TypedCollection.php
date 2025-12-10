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
    protected static string $type;

    /**
     * @throws UnexpectedValueException
     */
    public function __construct(...$values)
    {
        if(!isset(static::$type)) {
            throw new UnexpectedValueException('Type must be defined for TypedCollection');
        }
        array_walk($values, static fn($value) => $value instanceof self::$type ?: new self::$type($value));
        $this->values = $values;
    }

    /**
     * @throws UnexpectedValueException
     */
    public static function fromArray(array $config, ?string $type = null): self
    {
        $class = $type ?? static::$type;
        return new static(...array_map(static fn($item) => $item instanceof $class ? $item : new $class($item), $config));
    }
}