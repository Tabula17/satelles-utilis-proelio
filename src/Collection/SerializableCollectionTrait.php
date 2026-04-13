<?php

namespace Tabula17\Satelles\Utilis\Collection;

use JsonSerializable;

/**
 * Trait para manejar serialización de objetos en colecciones
 */
trait SerializableCollectionTrait
{
    /**
     * Serializa un valor, manejando objetos y arrays recursivamente
     */
    protected function serializeValue(mixed $value): mixed
    {
        if (is_object($value)) {
            return [
                '__type' => get_class($value),
                '__data' => $this->extractObjectData($value)
            ];
        }

        if (is_array($value)) {
            return array_map([$this, 'serializeValue'], $value);
        }

        return $value;
    }

    /**
     * Extrae los datos de un objeto para serialización
     */
    protected function extractObjectData(object $object): array
    {
        // Prioridad: __serialize() (método mágico de PHP)
        if (method_exists($object, '__serialize')) {
            return $object->__serialize();
        }

        // Segundo: JsonSerializable
        if ($object instanceof JsonSerializable) {
            return $object->jsonSerialize();
        }

        // Tercero: propiedades públicas
        $data = [];
        $reflection = new \ReflectionClass($object);

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $data[$property->getName()] = $property->getValue($object);
        }

        return $data;
    }

    /**
     * Deserializa un valor, reconstruyendo objetos si es necesario
     */
    protected function unserializeValue(mixed $value): mixed
    {
        if (is_array($value) && isset($value['__type'])) {
            return $this->reconstructObject($value['__type'], $value['__data'] ?? []);
        }

        if (is_array($value)) {
            return array_map([$this, 'unserializeValue'], $value);
        }

        return $value;
    }

    /**
     * Reconstruye un objeto a partir de su clase y datos
     */
    protected function reconstructObject(string $className, array $data): object
    {
        // Si la clase no existe, devolvemos un stdClass con los datos
        if (!class_exists($className)) {
            $object = new \stdClass();
            foreach ($data as $property => $value) {
                $object->$property = $value;
            }
            return $object;
        }

        try {
            $reflection = new \ReflectionClass($className);

            // Intentar crear objeto sin constructor
            $object = $reflection->newInstanceWithoutConstructor();

            // Primero: si tiene método __unserialize, usarlo
            if (method_exists($object, '__unserialize')) {
                $object->__unserialize($data);
                return $object;
            }

            // Segundo: asignar propiedades directamente
            foreach ($data as $property => $value) {
                if ($reflection->hasProperty($property)) {
                    $prop = $reflection->getProperty($property);
                    $prop->setAccessible(true);
                    $prop->setValue($object, $value);
                } elseif (property_exists($object, $property)) {
                    $object->$property = $value;
                }
            }

            return $object;
        } catch (\Exception $e) {
            // En caso de error, devolver stdClass
            $object = new \stdClass();
            foreach ($data as $property => $value) {
                $object->$property = $value;
            }
            return $object;
        }
    }

    /**
     * Clona un valor recursivamente
     */
    protected function cloneValue(mixed $value): mixed
    {
        if (is_object($value)) {
            return clone $value;
        }

        if (is_array($value)) {
            return array_map([$this, 'cloneValue'], $value);
        }

        return $value;
    }
}