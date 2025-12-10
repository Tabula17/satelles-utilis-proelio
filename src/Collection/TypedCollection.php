<?php

namespace Tabula17\Satelles\Utilis\Collection;

use Tabula17\Satelles\Utilis\Collection\GenericCollection;

/**
 * Represents a typed collection that enforces all items to be instances of a specified type.
 *
 * This class extends the functionality of GenericCollection while allowing strict type consistency
 * for the contained elements. The type is defined at runtime and validated upon creation of the collection,
 * ensuring that all elements conform to the defined type.
 */
class TypedCollection extends GenericCollection
{
    protected static string $type;

    public function __construct(string $type, array $values)
    {
        self::$type = $type;
        array_walk($values, static fn($value) => $value instanceof self::$type ?: new self::$type($value));
        $this->values = $values;
    }
    public static function fromArray(array $config, ?string $type = null): self
    {
        $class = $type ?? static::$type;
        return new self($class, ...array_map(static fn($item) => $item instanceof $class ? $item : new $class($item), $config));
    }
}