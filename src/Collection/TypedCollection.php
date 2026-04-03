<?php

namespace Tabula17\Satelles\Utilis\Collection;

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

    private static array $primitive_types = ['bool', 'int', 'float', 'string', 'array', 'object', 'iterable', 'resource', 'null'];

    /**
     * @throws UnexpectedValueException
     */
    public function __construct(...$values)
    {
        $type = static::getType();
        if (empty($type)) {
            throw new UnexpectedValueException('Type must be defined for TypedCollection');
        }
        if ($type === 'callable') {
            throw new UnexpectedValueException('Callable is not a valid type for TypedCollection, use CallableCollection instead');
        }
        array_walk($values, static fn($value) => static::cast($value));
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
            $check = "is_$value";
            if (!$check($value)) {
                if ($class === 'resource' || $class === 'iterable') {
                    throw new UnexpectedValueException("Value must be of type $class");
                }
                settype($value, $class);
            }
            return $value;
        }
        if (!class_exists($class) && !interface_exists($class)) {
            throw new UnexpectedValueException("Class $class does not exist");
        }
        if (!($value instanceof $class) && !class_exists($class) && interface_exists($class)) {
            throw new UnexpectedValueException("Value must implement $class but is of type " . get_class($value) . ". Cannot cast to $class");
        }
        return $value instanceof $class ? $value : new $class($value);
    }

    /**
     * @throws UnexpectedValueException
     */
    public static function fromArray(array $config, ?string $type = null): static
    {
        return new static(...array_map(static fn($item) => static::cast($item, $type), $config));
    }

    public function add(mixed $value): void
    {
        $this->values[] = static::cast($value);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $value = static::cast($value);
        if ($offset === null) {
            $this->values[] = $value;
        } else {
            $this->values[$offset] = $value;
        }
    }


    public function set(mixed $key, mixed $value): void
    {
        $this->values[$key] =  static::cast($value);
    }


    public function addIfNotExist(mixed $value): void
    {
        if (!in_array($value, $this->values, true)) {
            $this->values[] =  static::cast($value);
        }
    }
}