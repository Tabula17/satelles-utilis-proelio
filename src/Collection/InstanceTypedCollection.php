<?php

namespace Tabula17\Satelles\Utilis\Collection;

use Tabula17\Satelles\Utilis\Collection\GenericCollection;
use Tabula17\Satelles\Utilis\Exception\InvalidArgumentException;
use Tabula17\Satelles\Utilis\Exception\UnexpectedValueException;
use Tabula17\Satelles\Utilis\Trait\CastTypeTrait;

class InstanceTypedCollection extends GenericCollection
{
    use CastTypeTrait {
        cast as protected __cast;
    }

    public function __construct(protected readonly string $type)
    {
    }

    public function cast(mixed $value, ?string $type = null, bool $silent = true)
    {
        $class = $this->type;
        return static::__cast($value, $type ?? $class, $silent);
    }

    /**
     * @throws UnexpectedValueException
     * @throws InvalidArgumentException
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->add($value);
        } else {
            $this->set($offset, $value);
        }
    }


    /**
     * @throws UnexpectedValueException
     * @throws InvalidArgumentException
     * @throws \Throwable
     */
    public function set(mixed $key, mixed $value): void
    {
        if ($key === null) {
            throw new InvalidArgumentException("Cannot set null key, use add() instead");
        }
        $value = $this->cast($value);

        parent::set($key, $value);
    }


    /**
     * @throws UnexpectedValueException
     * @throws \Throwable
     */
    public function addIfNotExist(mixed $value, bool $strict = true): bool
    {
        return parent::addIfNotExist($this->cast($value), true);
    }

    /**
     * @throws UnexpectedValueException
     * @throws \Throwable
     */
    public function contains(mixed $value, bool $strict = true): bool
    {
        if ($strict) {
            return parent::contains($this->cast($value));
        }
        return parent::contains($value);
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Throwable
     * @throws UnexpectedValueException
     */
    public function load(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
    }
}