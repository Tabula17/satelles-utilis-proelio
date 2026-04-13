<?php

namespace Tabula17\Satelles\Utilis\Collection;

use Tabula17\Satelles\Utilis\Exception\InvalidArgumentException;
use Tabula17\Satelles\Utilis\Exception\UnexpectedValueException;
use Throwable;

/**
 * Represents a typed collection that enforces all items to be instances of a specified type.
 *
 * This class extends the functionality of GenericCollection while allowing strict type consistency
 * for the contained elements. The type is defined at runtime and validated upon creation of the collection,
 * ensuring that all elements conform to the defined type.
 */
abstract class TypedCollection extends GenericCollection
{

    private static array $primitive_types = ['bool', 'int', 'float', 'string', 'array', 'object', 'iterable', 'resource', 'null'];

    /**
     * @throws UnexpectedValueException
     */
    public function __construct(...$values)
    {
        $type = static::getType();
        if ($type === '') {
            throw new UnexpectedValueException('Type must be defined for TypedCollection');
        }
        if ($type === 'callable') {
            throw new UnexpectedValueException('Callable is not a valid type for TypedCollection, use CallableCollection instead');
        }
        array_walk($values, static fn(&$value) => static::cast($value));
        $this->values = $values;
    }

    abstract protected static function getType(): string;

    /**
     * @throws UnexpectedValueException
     */
    public static function cast(mixed $value, ?string $type = null)
    {
        $class = static::getType();
        if (isset($type) && is_subclass_of($type, $class)) {
            $class = $type;
        }
        if (in_array(strtolower($class), static::$primitive_types, true)) {
            $class = strtolower($class);
            $check = "is_$class";
            if (!$check($value)) {
                if ($class === 'resource' || $class === 'iterable') {
                    throw new UnexpectedValueException("Value must be of type $class");
                }
                settype($value, $class);
            }
            return $value;
        }
        if (!class_exists($class) && !interface_exists($class)) {
            throw new UnexpectedValueException("Class or Interface $class does not exist");
        }
        if (is_object($value)) {
            if (!($value instanceof $class)) {
                if (interface_exists($class)) {
                    throw new UnexpectedValueException("Value must implement $class. Cannot cast to $class");
                }
                throw new UnexpectedValueException("Value must be of type $class");
            }
            return $value;
        }
        try {
            return new $class($value);
        } catch (\Throwable $e) {
            throw new UnexpectedValueException("Unable to instantiate $class from value", 0, $e);
        }
    }


    /**
     * @throws UnexpectedValueException
     */
    public static function fromArray(array $config): static
    {
        $values = [];

        foreach ($config as $key => $item) {
            try {
                $values[$key] = static::cast($item);
            } catch (\Throwable $e) {
                continue;
            }
        }

        return new static(...$values);
    }

    public function add(mixed $value): void
    {
        $this->values[] = static::cast($value);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->add($value);
        } else {
            $this->set($offset, $value);
        }
    }


    public function set(mixed $key, mixed $value): void
    {
        if ($key === null) {
            throw new InvalidArgumentException("Cannot set null key, use add() instead");
        }
        $this->values[$key] = static::cast($value);
    }


    public function addIfNotExist(mixed $value, bool $strict = true): bool
    {
        return parent::addIfNotExist(static::cast($value), true);
    }
}