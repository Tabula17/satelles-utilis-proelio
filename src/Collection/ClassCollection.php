<?php

namespace Tabula17\Satelles\Utilis\Collection;

use Tabula17\Satelles\Utilis\Exception\UnexpectedValueException;

class ClassCollection extends GenericCollection
{
    public function __construct(...$values)
    {

        array_filter($values, static fn($value) => static::checkType($value));
        $this->values = $values;
    }
    protected static function checkType($value): string|false
    {
        if(is_object($value)) {
            $value = get_class($value);
        }
        return class_exists($value);
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
        $value = static::checkType($value);
        if ($offset === null) {
            $this->values[] = $value;
        } else {
            $this->values[$offset] = $value;
        }
    }


    public function set(mixed $key, mixed $value): void
    {
        $this->values[$key] =  static::checkType($value);
    }


    public function addIfNotExist(mixed $value): void
    {
        if (!in_array($value, $this->values, true)) {
            $this->values[] =  static::checkType($value);
        }
    }
}