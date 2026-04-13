<?php

namespace Tabula17\Satelles\Utilis\Collection;

use Tabula17\Satelles\Utilis\Exception\InvalidArgumentException;
use Tabula17\Satelles\Utilis\Interface\EnumMethodsInterface;
use UnitEnum;

abstract class TypedEnumCollection extends GenericCollection
{
    /**
     * @throws InvalidArgumentException
     */
    public function __construct(...$values)
    {
        $enumType = static::getEnumType();

        if (!enum_exists($enumType)) {
            throw new InvalidArgumentException("Enum type $enumType does not exist");
        }

        $this->values = [];
        foreach ($values as $value) {
            $this->values[] = static::cast($value);
        }
    }

    abstract protected static function getEnumType(): string;

    /**
     * @throws InvalidArgumentException
     */
    public static function cast(mixed $value): UnitEnum
    {
        $enum = static::getEnumType();

        if (!enum_exists($enum)) {
            throw new InvalidArgumentException("Enum type $enum does not exist");
        }

        if ($value instanceof $enum) {
            return $value;
        }

        if (is_subclass_of($enum, \BackedEnum::class)) {
            $enumValue = $enum::tryFrom($value);
            if ($enumValue instanceof $enum) {
                return $enumValue;
            }

            throw new InvalidArgumentException("Value $value is not a valid backed enum value for $enum");
        }

        if (is_subclass_of($enum, EnumMethodsInterface::class)) {
            if (!$enum::isValid($value)) {
                throw new InvalidArgumentException("Value $value is not a valid value for enum $enum");
            }

            $enumValue = $enum::fromValue($value);
            if ($enumValue instanceof $enum) {
                return $enumValue;
            }

            throw new InvalidArgumentException("Value $value could not be converted to enum $enum");
        }

        throw new InvalidArgumentException("Cannot cast value $value to enum $enum");
    }

    public function add(mixed $value): void
    {
        $this->values[] = static::cast($value);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->add($value);
            return;
        }

        $this->set($offset, $value);
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
        $value = static::cast($value);

        if (in_array($value, $this->values, true)) {
            return false;
        }

        $this->values[] = $value;
        return true;
    }

    /**
     * @throws InvalidArgumentException
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
}