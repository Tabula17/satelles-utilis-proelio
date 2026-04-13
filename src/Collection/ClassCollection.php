<?php

namespace Tabula17\Satelles\Utilis\Collection;

use Tabula17\Satelles\Utilis\Exception\UnexpectedValueException;

class ClassCollection extends GenericCollection
{
    public static ?string $type = null;
    public function __construct(...$values)
    {

        array_filter($values, static fn($value) => static::checkType($value));
        $this->values = $values;
    }

    protected static function checkType($value): bool
    {
        if (is_object($value)) {
            $value = get_class($value);
        }
        $valid = class_exists($value);
        if($valid && static::$type!==null)
        {
            if(!class_exists(static::$type))
            {
                trigger_error("The specified type " . static::$type . " does not exist.", E_USER_WARNING);
                return false;
            }
            $valid = is_subclass_of($value, static::$type);
            if(!$valid){
                trigger_error("The specified $value is not subclass of " . static::$type, E_USER_WARNING);
            }
        }
        return $valid;
    }

    public static function fromArray(array $config, ?string $type = null): static
    {
        return new static(...$config);
    }

    public function add(mixed $value): void
    {
        $this->values[] = static::checkType($value);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (static::checkType($value)) {
            if ($offset === null) {
                $this->values[] = $value;
            } else {
                $this->values[$offset] = $value;
            }
        }
    }


    public function set(mixed $key, mixed $value): void
    {
        if (static::checkType($value)) {
            $this->values[$key] = static::checkType($value);
        }
    }


    public function addIfNotExist(mixed $value, bool $strict = true): bool
    {
        if (static::checkType($value) && !in_array($value, $this->values, true)) {
            $this->values[] = static::checkType($value);
        }
        return false;
    }
}