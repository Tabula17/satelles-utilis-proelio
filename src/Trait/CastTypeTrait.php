<?php

namespace Tabula17\Satelles\Utilis\Trait;

use Tabula17\Satelles\Utilis\Exception\UnexpectedValueException;
use Throwable;

trait CastTypeTrait
{
    private static array $primitive_types = [
        'bool' => 'boolean',
        'boolean' => 'boolean',
        'int' => 'integer',
        'integer' => 'integer',
        'float' => 'float',
        'double' => 'float',
        'string' => 'string',
        'array' => 'array',
        'object' => 'object',
        'iterable' => 'iterable',
        'resource' => 'resource',
        'null' => 'null'
    ];

    /**
     * @throws Throwable
     * @throws UnexpectedValueException
     */
    public static function cast(mixed $value, string $type, bool $silent = true): mixed
    {
        try {

            $lowerType = strtolower($type);
            // 1. Manejo de Tipos Primitivos
            if (array_key_exists($lowerType, static::$primitive_types)) {
                $mappedType = static::$primitive_types[$lowerType];
                // Normalizar funciones de chequeo (ej: bool -> is_bool)
                $correct = [
                    'boolean' => 'bool',
                    'integer' => 'int'
                ];
                $checkType = $correct[$lowerType] ?? $lowerType;
                $checkFunc = "is_$checkType";

                if (!$checkFunc($value)) {
                    if ($mappedType === 'resource' || $mappedType === 'iterable') {
                        throw new UnexpectedValueException("Value must be of type $type");
                    }
                    if ($mappedType === 'null') {
                        return null;
                    }

                    // settype necesita el nombre compatible (integer/boolean)
                    settype($value, $mappedType);
                }
                return $value;
            }
            // 2. Validación de Clases e Interfaces
            if (!class_exists($type) && !interface_exists($type)) {
                throw new UnexpectedValueException("Class or Interface $type does not exist");
            }
            if (is_object($value)) {
                if (!($value instanceof $type)) {
                    if (interface_exists($type)) {
                        throw new UnexpectedValueException("Value must implement $type. Cannot cast to $type");
                    }
                    throw new UnexpectedValueException("Value must be of type $type");
                }
                return $value;
            }
            // 3. Instanciación Dinámica de Objetos
            try {
                return new $type($value);
            } catch (Throwable) {
                try {
                    return new $type(...(is_array($value) ? $value : [$value]));
                } catch (Throwable $e) {
                    try {
                        // Aseguramos que el array use índices numéricos para evitar errores de argumentos nombrados
                        $args = is_array($value) ? array_values($value) : [$value];
                        return new $type(...$args);
                    } catch (Throwable $e) {
                        throw new UnexpectedValueException("Unable to instantiate $type from value", 0, $e);
                    }
                }
            }
        } catch (Throwable $e) {
            if ($silent) {
                return null;
            }
            throw $e;
        }
    }

}