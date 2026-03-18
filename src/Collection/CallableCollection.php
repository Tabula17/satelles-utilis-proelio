<?php

namespace Tabula17\Satelles\Utilis\Collection;

use Tabula17\Satelles\Utilis\Collection\TypedCollection;
use Tabula17\Satelles\Utilis\Exception\UnexpectedValueException;

class CallableCollection extends GenericCollection
{

    public function __construct(...$values)
    {
        array_walk($values, static fn($value) => static::cast($value));
        $this->values = $values;
    }

    private static function wrapAsCallable($value): callable
    {
        return $value instanceof \Closure ? $value : static fn() => $value;
    }

    public static function cast(mixed $value)
    {
        if (!is_callable($value)) {
            $value = static::wrapAsCallable($value);
        }
        return $value;
    }

    public static function fromArray(array $config, ?string $type = null): self
    {
        $class = $type ?? static::getType();
        return new static(...array_map(static fn($item) => static::cast($item), $config));
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

    protected static function getType(): string
    {
        return 'callable';
    }
}
