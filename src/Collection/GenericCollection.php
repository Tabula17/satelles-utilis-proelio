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


    /**
     * @return Traversable<array-key, T>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->values);
    }

    /**
     * Convierte la colección a array con soporte para objetos anidados.
     *
     * @return array<array-key, mixed>
     */
    public function toArray(): array
    {
        return array_map(function ($value) {
            return $this->normalizeValue($value);
        }, $this->values);
    }

    /**
     * Normaliza un valor para su representación en array.
     *
     * @param mixed $value
     * @return mixed
     */
    protected function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof JsonSerializable) {
            return $value->jsonSerialize();
        }

        if (is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }

        if (is_array($value)) {
            return array_map([$this, 'normalizeValue'], $value);
        }

        return $value;
    }

    /**
     * Serializa la colección a un array compatible con JSON.
     *
     * @return array<array-key, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Devuelve el número de elementos en la colección.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->values);
    }

    /**
     * Determina si la colección está vacía.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->values);
    }

    /**
     * Obtiene un valor por clave con soporte para notación de punto (ej: 'user.profile.name').
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (str_contains($key, '.')) {
            return $this->getNestedValue($key, $default);
        }

        return $this->values[$key] ?? $default;
    }

    /**
     * Obtiene un valor anidado usando notación de punto.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
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
     * Verifica si existe una clave con soporte para notación de punto.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        if (str_contains($key, '.')) {
            return $this->hasNestedKey($key);
        }

        return isset($this->values[$key]);
    }

    /**
     * Verifica si existe una clave anidada usando notación de punto.
     *
     * @param string $key
     * @return bool
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
     * Obtiene un valor o ejecuta callback si no existe.
     *
     * @param string $key
     * @param callable $callback
     * @return mixed
     */
    public function getOrElse(string $key, callable $callback): mixed
    {
        return $this->has($key) ? $this->get($key) : $callback();
    }

    /**
     * Busca el primer elemento que cumpla con la condición del callback.
     *
     * @param callable(T, array-key): bool $callback
     * @return T|null
     */
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

    /**
     * Busca la clave del primer elemento que cumpla con la condición del callback.
     *
     * @param callable(T, array-key): bool $callback
     * @return array-key|null
     */
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

    /**
     * Filtra la colección usando un callback (recibe valor y clave).
     *
     * @param callable(T, array-key): bool $callback
     * @return static
     */
    public function filter(callable $callback): static
    {
        $values = array_filter($this->values, $callback, ARRAY_FILTER_USE_BOTH);
        $instance = new static();
        foreach ($values as $key => $value) {
            $instance->set($key, $value);
        }
        return $instance;
    }

    /**
     * Filtra la colección por sus claves.
     *
     * @param callable(array-key): bool $callback
     * @return static
     */
    public function filterKeys(callable $callback): static
    {
        $values = array_filter($this->values, $callback, ARRAY_FILTER_USE_KEY);
        $instance = new static();
        foreach ($values as $key => $value) {
            $instance->set($key, $value);
        }
        return $instance;
    }

    /**
     * Alias de filter(). Filtra la colección usando valor y clave.
     *
     * @param callable(T, array-key): bool $callback
     * @return static
     */
    public function filterKeyOrValue(callable $callback): static
    {
        return $this->filter($callback);
    }

    /**
     * Reduce la colección a un solo valor.
     *
     * @param callable(mixed, T): mixed $callback
     * @param mixed $initial
     * @return mixed
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->values, $callback, $initial);
    }

    /**
     * Aplica un callback a cada elemento y devuelve un array con los resultados.
     *
     * @template U
     * @param callable(T, array-key): U $callback
     * @return array<array-key, U>
     */
    public function map(callable $callback): array
    {
        $result = [];
        foreach ($this->values as $key => $value) {
            $result[$key] = $callback($value, $key);
        }
        return $result;
    }

    /**
     * Transforma la colección aplicando un callback y manteniendo la instancia.
     *
     * @param callable(T, array-key): T $callback
     * @return static
     */
    public function transform(callable $callback): static
    {
        $instance = new static();
        foreach ($this->values as $key => $value) {
            $instance->set($key, $callback($value, $key));
        }
        return $instance;
    }

    /**
     * Determina si al menos un elemento cumple la condición.
     *
     * @param callable(T, array-key): bool $callback
     * @return bool
     */
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

    /**
     * Determina si todos los elementos cumplen la condición.
     *
     * @param callable(T, array-key): bool $callback
     * @return bool
     */
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

    /**
     * Obtiene el primer elemento de la colección.
     *
     * @return T|null
     */
    public function first(): mixed
    {
        return $this->values[array_key_first($this->values)] ?? null;
    }

    /**
     * Obtiene el último elemento de la colección.
     *
     * @return T|null
     */
    public function last(): mixed
    {
        return $this->values[array_key_last($this->values)] ?? null;
    }

    /**
     * Extrae y devuelve el último elemento de la colección.
     *
     * @return T|null
     */
    public function pop(): mixed
    {
        return array_pop($this->values);
    }

    /**
     * Añade uno o más elementos al final de la colección.
     *
     * @param T ...$values
     * @return int Nuevo número de elementos.
     */
    public function push(...$values): int
    {
        return array_push($this->values, ...$values);
    }

    /**
     * Extrae y devuelve el primer elemento de la colección.
     *
     * @return T|null
     */
    public function shift(): mixed
    {
        return array_shift($this->values);
    }

    /**
     * Añade uno o más elementos al inicio de la colección.
     *
     * @param T ...$values
     * @return int Nuevo número de elementos.
     */
    public function unshift(...$values): int
    {
        return array_unshift($this->values, ...$values);
    }

    /**
     * Elimina todas las ocurrencias de un valor específico.
     *
     * @param T $value
     * @param bool $strict
     * @return static
     */
    public function remove(mixed $value, bool $strict = true): static
    {
        $this->values = array_filter(
            $this->values,
            static fn($item) => $strict ? $item !== $value : $item != $value
        );
        return $this;
    }

    /**
     * Elimina un elemento por su clave/índice.
     *
     * @param int|string $index
     * @return static
     */
    public function removeAt(int|string $index): static
    {
        unset($this->values[$index]);
        return $this;
    }

    /**
     * Vacía la colección.
     *
     * @return static
     */
    public function clear(): static
    {
        $this->values = [];
        return $this;
    }

    /**
     * Establece un valor para una clave específica.
     *
     * @param mixed $key
     * @param T $value
     * @return void
     */
    public function set(mixed $key, mixed $value): void
    {
        $this->values[$key] = $value;
    }

    /**
     * Añade un elemento a la colección.
     *
     * @param T $value
     * @return void
     */
    public function add(mixed $value): void
    {
        $this->values[] = $value;
    }

    /**
     * Añade un elemento solo si no existe ya en la colección.
     *
     * @param T $value
     * @param bool $strict
     * @return bool True si se añadió, false si ya existía.
     */
    public function addIfNotExist(mixed $value, bool $strict = true): bool
    {
        if (!in_array($value, $this->values, $strict)) {
            $this->values[] = $value;
            return true;
        }
        return false;
    }

    /**
     * Verifica si la colección contiene un valor específico.
     *
     * @param T $value
     * @param bool $strict
     * @return bool
     */
    public function contains(mixed $value, bool $strict = true): bool
    {
        return in_array($value, $this->values, $strict);
    }

    /**
     * Obtiene valores únicos de la colección.
     *
     * @param bool $strict
     * @return static
     */
    public function unique(bool $strict = true): static
    {
        $values = array_unique($this->values, $strict ? SORT_REGULAR : SORT_STRING);
        $instance = new static();
        foreach ($values as $key => $value) {
            $instance->set($key, $value);
        }
        return $instance;
    }

    /**
     * Revierte el orden de la colección.
     *
     * @return static
     */
    public function reverse(): static
    {
        $values = array_reverse($this->values);
        $instance = new static();
        foreach ($values as $key => $value) {
            $instance->set($key, $value);
        }
        return $instance;
    }

    /**
     * Ordena la colección por sus valores.
     *
     * @param callable|null $callback
     * @return static
     */
    public function sort(callable|null $callback = null): static
    {
        $values = $this->values;
        if ($callback === null) {
            sort($values);
        } else {
            usort($values, $callback);
        }
        $instance = new static();
        foreach ($values as $key => $value) {
            $instance->set($key, $value);
        }
        return $instance;
    }

    /**
     * Ordena la colección por sus claves.
     *
     * @param callable|null $callback
     * @return static
     */
    public function sortKeys(callable|null $callback = null): static
    {
        $values = $this->values;
        if ($callback === null) {
            ksort($values);
        } else {
            uksort($values, $callback);
        }
        $instance = new static();
        foreach ($values as $key => $value) {
            $instance->set($key, $value);
        }
        return $instance;
    }

    /**
     * Extrae una porción de la colección.
     *
     * @param int $offset
     * @param int|null $length
     * @return static
     */
    public function slice(int $offset, int|null $length = null): static
    {
        $values = array_slice($this->values, $offset, $length, true);
        $instance = new static();
        foreach ($values as $key => $value) {
            $instance->set($key, $value);
        }
        return $instance;
    }

    /**
     * Obtiene las claves de la colección.
     *
     * @return array<int, array-key>
     */
    public function keys(): array
    {
        return array_keys($this->values);
    }

    /**
     * Obtiene los valores de la colección como un array indexado.
     *
     * @return array<int, T>
     */
    public function values(): array
    {
        return array_values($this->values);
    }

    /**
     * Combina esta colección con otra.
     *
     * @param GenericCollection<T>|array<array-key, T> $collection
     * @param bool $preserveKeys
     * @return static
     */
    public function merge(self|array $collection, bool $preserveKeys = false): static
    {
        $values = $this->values;
        $incoming = $collection instanceof self ? $collection->extractAll() : $collection;

        if ($preserveKeys) {
            $values = array_merge($values, $incoming);
        } else {
            $values = array_merge(array_values($values), array_values($incoming));
        }
        $instance = new static();
        foreach ($values as $key => $value) {
            $instance->set($key, $value);
        }
        return $instance;
    }

    /**
     * Devuelve todos los elementos de la colección.
     *
     * @return array<array-key, T>
     */
    public function extractAll(): array
    {
        return $this->values;
    }

    /**
     * Aplica un callback a cada elemento de la colección.
     *
     * @param callable(T, array-key): void $callback
     * @return static
     */
    public function each(callable $callback): static
    {
        foreach ($this->values as $key => $value) {
            $callback($value, $key);
        }
        return $this;
    }

    /**
     * Aplica el callback hasta que devuelva false.
     *
     * @param callable(T, array-key): bool $callback
     * @return static
     */
    public function until(callable $callback): static
    {
        foreach ($this->values as $key => $value) {
            if (!$callback($value, $key)) {
                break;
            }
        }
        return $this;
    }

    /**
     * Agrupa la colección por una clave o callback.
     *
     * @param (callable(T): (string|int))|string $key
     * @return array<string|int, array<int, T>>
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
     * Obtiene uno o varios elementos aleatorios de la colección.
     *
     * @param int $number Cantidad de elementos a obtener.
     * @return T|array<int, T>|null
     */
    public function random(int $number = 1): mixed
    {
        $count = count($this->values);

        if ($count === 0 || $number <= 0) {
            return $number === 1 ? null : [];
        }

        $actualNumber = min($number, $count);

        if ($actualNumber === 1) {
            return $this->values[array_rand($this->values)];
        }

        $keys = array_rand($this->values, $actualNumber);
        $result = [];

        foreach ((array)$keys as $key) {
            $result[] = $this->values[$key];
        }

        return $result;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->values[$offset]);
    }

    /**
     * @param mixed $offset
     * @return T|null
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->values[$offset] ?? null;
    }

    /**
     * @param mixed $offset
     * @param T $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->values[] = $value;
        } else {
            $this->values[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->values[$offset]);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function __serialize(): array
    {
        return array_map([$this, 'serializeValue'], $this->values);
    }

    /**
     * @param array<array-key, mixed> $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->values = array_map([$this, 'unserializeValue'], $data);
    }

    /**
     * Realiza una clonación profunda de los elementos.
     */
    public function __clone()
    {
        $this->values = array_map([$this, 'cloneValue'], $this->values);
    }

    /**
     * Métodos mágicos para acceso como propiedades.
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return $this->get($name);
    }

    /**
     * @param string $name
     * @param T $value
     * @return void
     */
    public function __set(string $name, mixed $value): void
    {
        $this->set($name, $value);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return $this->has($name);
    }

    /**
     * @param string $name
     * @return void
     */
    public function __unset(string $name): void
    {
        $this->removeAt($name);
    }

    /**
     * Convierte la colección a una cadena JSON.
     *
     * @return string
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