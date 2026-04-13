<?php

namespace Tabula17\Satelles\Utilis\Collection;

use Tabula17\Satelles\Utilis\Exception\InvalidArgumentException;
use Tabula17\Satelles\Utilis\Interface\EnumMethodsInterface;
use UnitEnum;

abstract class TypedEnumCollection extends GenericCollection
{
    public function __construct(...$values)
    {
        if (!enum_exists(static::getEnumType())) {
            throw new InvalidArgumentException("Enum type " . static::getEnumType() . " does not exist");
        }
        $this->values = array_filter(array_map(static fn($v) => static::castOrExclude($v), $values));
    }

    abstract protected static function getEnumType(): string;

    public static function castOrExclude(mixed $value): UnitEnum|null
    {
        $enum = static::getEnumType();
        if ($value instanceof $enum) {
            return $value;
        }
        if (is_a($enum, EnumMethodsInterface::class, true)) {
            return $enum::fromValue($value);
        }
        return null;
    }


    public function add(mixed $value): void
    {
        $value = static::castOrExclude($value);
        if ($value) {
            $this->values[] = static::castOrExclude($value);
        }
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
        $value = static::castOrExclude($value);
        if ($value) {
            $this->values[$key] = $value;
        }
    }


    public function addIfNotExist(mixed $value, bool $strict = true): bool
    {
        $value = static::castOrExclude($value);
        if ($value) {
            return parent::addIfNotExist($value, true);
        }
        return false;
    }

    public static function fromArray(array $config): static
    {
        return new static(...$config);
    }
}