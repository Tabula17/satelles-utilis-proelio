<?php

namespace Tabula17\Satelles\Utilis\Config;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;
use ReflectionUnionType;
use Traversable;

/**
 * AbstractDescriptor provides a base implementation for an object that supports
 * ArrayAccess, IteratorAggregate, and JsonSerializable interfaces. It offers a
 * standard way to manage properties, serialization, and data manipulation.
 */
abstract class AbstractDescriptor implements ArrayAccess, IteratorAggregate, JsonSerializable
{
    /**
     *
     * @var array
     */
    private array $publicProperties = [];

    public function __construct(?array $values = [])
    {
        $this->initPublicProperties();
        $this->loadProperties($values);
    }
    protected function initPublicProperties(): void
    {
        if (!empty($this->publicProperties)) {
            return;
        }
        $getPublicClassVars = static function (string $className) {
            return get_class_vars($className);
        };
        $unboundGetter = $getPublicClassVars->bindTo(null, null);
        $this->publicProperties = array_keys($unboundGetter(static::class));
    }


    /**
     * Establece un valor para una propiedad
     * @param string $property
     * @param mixed $value
     */
    public function set(string $property, mixed $value): void
    {
        if (property_exists($this, $property) && $this->isAccessible($property)) {
            $setterMethod = 'set' . ucfirst($property);
            if (method_exists($this, $setterMethod)) {
                $this->$setterMethod($value);
            } else {
                $this->$property = $value;
            }
        }
    }

    /**
     * Comprueba si una propiedad es accesible
     * @param string $property
     * @return bool
     */
    private function isAccessible(string $property): bool
    {
        return in_array($property, $this->publicProperties, true);
    }

    public function get(string $property): mixed
    {
        if (!$this->isAccessible($property)) {
            return null;
        }
        return $this->$property;
    }

    public function offsetExists(mixed $offset): bool
    {
        return property_exists($this, $offset) && isset($this->$offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {

        $this->set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        if ($this->offsetExists($offset)) {
            unset($this->$offset);
        }
    }

    /**
     * Convierte el objeto en un array
     * @return array
     */
    public function toArray(): array
    {
        $data = [];
        foreach (get_mangled_object_vars($this) as $property => $v) { //get_object_vars
            if (!$this->isAccessible($property)) {
                continue;
            }
            $value = $this->$property; // Llamamos al getter para obtener el valor de la propiedad (soporte para hooks!)
            if (is_object($value) && method_exists($value, 'toArray')) {
                $data[$property] = $value->toArray();
            } elseif (is_object($value) && method_exists($value, 'jsonSerialize')) {
                $data[$property] = $value->jsonSerialize();
            } elseif ($value instanceof \UnitEnum) {
                if ($value instanceof \BackedEnum) {
                    $data[$property] = $value->value;
                } else {
                    $data[$property] = $value->name;
                }
            } else {
                $data[$property] = $value;
            }
        }
        return $data;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->toArray());
    }

    public function loadProperties(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function hasProperty(string $property): bool
    {
        return property_exists($this, $property);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Devuelve un array con los valores de las propiedades inicializadas
     * IMPORTANTE: Al usar ReflectionClass la performance puede verse afectada
     * @return array
     */
    public function getInitialized(): array
    {
        $reflection = new ReflectionClass($this);
        $initializedProperties = array_filter(
            $reflection->getProperties(),
            fn($property) => $property->isInitialized($this)
        );

        $propertyNames = array_column($initializedProperties, 'name');

        return array_intersect_key(
            $this->toArray(),
            array_flip($propertyNames)
        );
    }

    /**
     * Devuelve un array con el modelo de la clase
     * @return array
     */
    public static function getModel(): array
    {
        $response = [];
        $reflection = new ReflectionClass(static::class);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        foreach ($properties as $property) {
            $prpopType = $property->getType();
            if ($prpopType instanceof ReflectionUnionType) {
                $type = [];
                foreach ($prpopType->getTypes() as $unionType) {
                    $type[] = $unionType->getName();
                }
                $type = implode('|', $type);
            } else {
                $type = $prpopType?->getName() ?? 'mixed';
            }

            if (is_a($type, AbstractDescriptor::class, true)) {
                $response[$property->getName()] = $type::class !== static::class ? $type::getModel() : $type::class;
            } else {

                $response[$property->getName()] = $type;
            }
        }
        return $response;
    }

    /**
     * Comprueba si un conjunto de datos coincide con el modelo definido.
     * Si $strict es true, verifica que todas las claves en $data existan en el modelo.
     * Si $strict es false, verifica que algunas claves en $data existan en el modelo.
     * Esto, en conjunto con el metodo AbstractDescriptor::fromArray, es útil para validar si un conjunto de datos puede ser convertido en una instancia del modelo.
     *
     * @param array $data Conjunto de datos a validar.
     * @param bool $strict Determina si la validación debe ser estricta.
     * @return bool Devuelve true si los datos coinciden según el nivel de validación definido, o false en caso contrario.
     */
    public static function matchModel(array $data, bool $strict = false): bool
    {
        $model = static::getModel();
        // if strict is true, check if all keys in $data exist in $model
        if ($strict) {
            return empty(array_diff_key($data, $model));
        }
        // if strict is false, check if some keys in $data exist in $model. If count keys are greater than count diff keys, return true
        return count(array_keys($data)) > count(array_diff_key($data, $model));
    }

    public function __serialize(): array
    {
        $defaultValues = get_class_vars(static::class);
        $currentValues = get_mangled_object_vars($this);
        $data = [];

        foreach ($currentValues as $property => $value) {
            if (!property_exists($this, $property)) {
                continue;
            }

            $defaultValue = $defaultValues[$property] ?? null;

            // Serializar solo si el valor es diferente al default
            if ($value !== $defaultValue) {
                $data[$property] = $value;
            }
        }
        return $data;
    }

    public function __unserialize(array $data): void
    {
        $this->initPublicProperties();
        $this->loadProperties($data);
    }

    public function __get(string $name)
    {
        return $this->get($name);
    }

    public function __set(string $name, $value): void
    {
        $this->set($name, $value);
    }

    public function __isset(string $name): bool
    {
        return property_exists($this, $name) && isset($this->$name);
    }

    public function __unset(string $name): void
    {
        unset($this->$name);
    }

    public function __clone()
    {
        $this->loadProperties($this->__serialize());
    }

    public static function fromArray(array $config): static
    {
        $instance = new static();
        foreach ($config as $key => $value) {
            $instance->set($key, $value);
        }
        return $instance;
    }
}