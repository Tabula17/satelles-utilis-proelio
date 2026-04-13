<?php

namespace Tabula17\Satelles\Utilis\Collection;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonException;
use Traversable;
use ArrayAccess;
use JsonSerializable;


/**
 * Abstract class representing a generic collection of items.
 * This class provides common methods to manipulate and access
 * the data stored within the collection. The class implements
 * IteratorAggregate for traversable behavior, ArrayAccess for array-like
 * manipulation, and JsonSerializable for JSON serialization.
 *
 * @template T
 * @implements IteratorAggregate<array-key, T>
 * @implements ArrayAccess<array-key, T>
 */
abstract class GenericCollection implements IteratorAggregate, ArrayAccess, JsonSerializable, Countable
{
    use SerializableCollectionTrait;

    protected array $values = [];


    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->values);
    }

    /**
     * Convierte la colección a array con soporte para objetos anidados
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->values as $key => $value) {
            $result[$key] = $this->normalizeValue($value);
        }
        return $result;
    }

    /**
     * Normaliza un valor para su representación en array
     */
    protected function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof JsonSerializable) {
            return $value->jsonSerialize();
        }

        if ($value instanceof self) {
            return $value->toArray();
        }

        if (is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }

        if (is_array($value)) {
            return array_map([$this, 'normalizeValue'], $value);
        }

        return $value;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function count(): int
    {
        return count($this->values);
    }

    public function isEmpty(): bool
    {
        return empty($this->values);
    }

    /**
     * Obtiene un valor por clave con soporte para notación de punto (ej: 'user.profile.name')
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (str_contains($key, '.')) {
            return $this->getNestedValue($key, $default);
        }

        return $this->values[$key] ?? $default;
    }

    /**
     * Obtiene un valor anidado usando notación de punto
     */
    protected function getNestedValue(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = $this->values;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Verifica si existe una clave con soporte para notación de punto
     */
    public function has(string $key): bool
    {
        if (str_contains($key, '.')) {
            return $this->hasNestedKey($key);
        }

        return isset($this->values[$key]);
    }

    /**
     * Verifica si existe una clave anidada usando notación de punto
     */
    protected function hasNestedKey(string $key): bool
    {
        $segments = explode('.', $key);
        $value = $this->values;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return false;
            }
            $value = $value[$segment];
        }

        return true;
    }

    /**
     * Obtiene un valor o ejecuta callback si no existe
     */
    public function getOrElse(string $key, callable $callback): mixed
    {
        return $this->has($key) ? $this->get($key) : $callback();
    }

    public function find(callable $callback): mixed
    {
        if (function_exists('array_find')) {
            return array_find($this->values, static fn($value, $key) => $callback($value, $key));
        }

        foreach ($this->values as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return null;
    }

    public function findKey(callable $callback): mixed
    {
        if (function_exists('array_find_key')) {
            return array_find_key($this->values, static fn($value, $key) => $callback($value, $key));
        }

        foreach ($this->values as $key => $value) {
            if ($callback($value, $key)) {
                return $key;
            }
        }

        return null;
    }

    public function filter(callable $callback): static
    {
        $filtered = array_filter($this->values, $callback, ARRAY_FILTER_USE_BOTH);
        return new static($filtered);
    }

    public function filterKeys(callable $callback): static
    {
        $filtered = array_filter($this->values, $callback, ARRAY_FILTER_USE_KEY);
        return new static($filtered);
    }

    public function filterKeyOrValue(callable $callback): static
    {
        $filtered = array_filter($this->values, $callback, ARRAY_FILTER_USE_BOTH);
        return new static($filtered);
    }

    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->values, $callback, $initial);
    }

    public function map(callable $callback): array
    {
        return array_map($callback, $this->values);
    }

    /**
     * Transforma la colección aplicando un callback y manteniendo la instancia
     */
    public function transform(callable $callback): static
    {
        $transformed = array_map($callback, $this->values);
        return new static($transformed);
    }

    public function some(callable $callback): bool
    {
        if (function_exists('array_any')) {
            return array_any($this->values, static fn($value, $key) => $callback($value, $key));
        }

        foreach ($this->values as $key => $value) {
            if ($callback($value, $key)) {
                return true;
            }
        }

        return false;
    }

    public function every(callable $callback): bool
    {
        if (function_exists('array_all')) {
            return array_all($this->values, static fn($value, $key) => $callback($value, $key));
        }

        foreach ($this->values as $key => $value) {
            if (!$callback($value, $key)) {
                return false;
            }
        }

        return true;
    }

    public function first(): mixed
    {
        return $this->values[array_key_first($this->values)] ?? null;
    }

    public function last(): mixed
    {
        return $this->values[array_key_last($this->values)] ?? null;
    }

    public function pop(): mixed
    {
        return array_pop($this->values);
    }

    public function push(...$values): int
    {
        return array_push($this->values, ...$values);
    }

    public function shift(): mixed
    {
        return array_shift($this->values);
    }

    public function unshift(...$values): int
    {
        return array_unshift($this->values, ...$values);
    }

    public function remove(mixed $value, bool $strict = true): void
    {
        $this->values = array_filter(
            $this->values,
            static fn($item) => $strict ? $item !== $value : $item != $value
        );
    }

    public function removeAt(int|string $index): void
    {
        unset($this->values[$index]);
    }

    public function clear(): void
    {
        $this->values = [];
    }

    public function set(mixed $key, mixed $value): void
    {
        $this->values[$key] = $value;
    }

    public function add(mixed $value): void
    {
        $this->values[] = $value;
    }

    public function addIfNotExist(mixed $value, bool $strict = true): bool
    {
        if (!in_array($value, $this->values, $strict)) {
            $this->values[] = $value;
            return true;
        }
        return false;
    }

    public function contains(mixed $value, bool $strict = true): bool
    {
        return in_array($value, $this->values, $strict);
    }

    /**
     * Obtiene valores únicos de la colección
     */
    public function unique(bool $strict = true): static
    {
        return new static(array_unique($this->values, $strict ? SORT_REGULAR : SORT_STRING));
    }

    /**
     * Revierte el orden de la colección
     */
    public function reverse(): static
    {
        return new static(array_reverse($this->values));
    }

    /**
     * Ordena la colección
     */
    public function sort(callable|null $callback = null): static
    {
        $values = $this->values;
        if ($callback === null) {
            sort($values);
        } else {
            usort($values, $callback);
        }
        return new static($values);
    }

    /**
     * Ordena la colección por claves
     */
    public function sortKeys(callable|null $callback = null): static
    {
        $values = $this->values;
        if ($callback === null) {
            ksort($values);
        } else {
            uksort($values, $callback);
        }
        return new static($values);
    }

    /**
     * Extrae una porción de la colección
     */
    public function slice(int $offset, int|null $length = null): static
    {
        return new static(array_slice($this->values, $offset, $length, true));
    }

    /**
     * Obtiene las claves de la colección
     */
    public function keys(): array
    {
        return array_keys($this->values);
    }

    /**
     * Obtiene los valores de la colección
     */
    public function values(): array
    {
        return array_values($this->values);
    }

    /**
     * Combina esta colección con otra
     */
    public function merge(GenericCollection|array $collection, bool $preserveKeys = false): static
    {
        $values = $this->values;
        $incoming = $collection instanceof self ? $collection->extractAll() : $collection;

        if ($preserveKeys) {
            $values = array_merge($values, $incoming);
        } else {
            $values = array_merge(array_values($values), array_values($incoming));
        }

        return new static($values);
    }

    public function extractAll(): array
    {
        return $this->values;
    }

    /**
     * Aplica un callback a cada elemento de la colección
     */
    public function each(callable $callback): void
    {
        foreach ($this->values as $key => $value) {
            $callback($value, $key);
        }
    }

    /**
     * Agrupa la colección por una clave o callback
     */
    public function groupBy(callable|string $key): array
    {
        $groups = [];

        foreach ($this->values as $item) {
            $groupKey = is_callable($key) ? $key($item) : ($item[$key] ?? null);

            if ($groupKey !== null) {
                $groups[$groupKey][] = $item;
            }
        }

        return $groups;
    }

    /**
     * Obtiene un valor aleatorio de la colección
     */
    public function random(int $number = 1): mixed
    {
        if ($number === 1) {
            return $this->values[array_rand($this->values)] ?? null;
        }

        $keys = array_rand($this->values, $number);
        $result = [];

        foreach ((array)$keys as $key) {
            $result[] = $this->values[$key];
        }

        return $result;
    }

    // Implementación de ArrayAccess
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->values[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->values[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->values[] = $value;
        } else {
            $this->values[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->values[$offset]);
    }

    // Serialización mejorada usando el trait
    public function __serialize(): array
    {
        return array_map([$this, 'serializeValue'], $this->values);
    }

    public function __unserialize(array $data): void
    {
        $this->values = array_map([$this, 'unserializeValue'], $data);
    }

    // Clonado mejorado usando el trait
    public function __clone()
    {
        $this->values = array_map([$this, 'cloneValue'], $this->values);
    }

    /**
     * Métodos mágicos para acceso como propiedades
     */
    public function __get(string $name): mixed
    {
        return $this->get($name);
    }

    public function __set(string $name, mixed $value): void
    {
        $this->set($name, $value);
    }

    public function __isset(string $name): bool
    {
        return $this->has($name);
    }

    public function __unset(string $name): void
    {
        $this->removeAt($name);
    }

    /**
     * Convierte la colección a string (JSON)
     */
    public function __toString(): string
    {
        try {
            return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return '{}';
        }
    }
}