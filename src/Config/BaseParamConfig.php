<?php

namespace Tabula17\Satelles\Utilis\Config;

use Closure;
use Tabula17\Satelles\Utilis\Config\AbstractDescriptor;
use Tabula17\Satelles\Utilis\Exception\UnexpectedValueException;
use Tabula17\Satelles\Utilis\Trait\CastTypeTrait;
use Throwable;

class BaseParamConfig extends AbstractDescriptor
{
    use CastTypeTrait;

    protected(set) string $name;
    protected(set) string $description = '';
    protected(set) string $type = 'string'
        {
            set {
                if ($value === 'null') {
                    $this->allowNull = true;
                }
                $this->type = $value;
            }
        }
    private mixed $rawValue;
    private bool $isInitialized = false;
    protected(set) mixed $value
        {
            /**
             * @throws UnexpectedValueException
             * @throws Throwable
             */
            set {
                if ($this->mutable === false && $this->isInitialized) {
                    return;
                }
                try {
                    if (isset($this->prepareValue)) {
                        $value = ($this->prepareValue)($value);
                    }
                    $this->rawValue = self::cast($value, $this->type, $this->allowNull);
                    $this->isInitialized = true;
                } catch (Throwable $exception) {
                    throw new UnexpectedValueException('Error al guardar el valor de `' . $this->name . '`: ' . $exception->getMessage(), $exception->getCode(), $exception);
                }
            }
            get {
                if ($this->required || $this->isInitialized) {
                    try {
                        // Si el valor es nulo y no es permitido, retornar el valor por defecto. Si no hay valor por defecto, lanzar excepción si allowNull es falso.
                        return $this->rawValue ?? self::cast($this->defaultValue, $this->type, $this->allowNull);
                    } catch (Throwable $exception) {
                        throw new UnexpectedValueException('Error al guardar el valor de `' . $this->name . '`: ' . $exception->getMessage(), $exception->getCode(), $exception);
                    }
                }
                return $this->rawValue;
            }
        }
    protected(set) mixed $defaultValue = null;
    protected(set) bool $required = false;
    protected(set) bool $allowNull = false
        {
            set {
                if ($this->type === 'null') {
                    $value = true;
                }
                $this->allowNull = $value;
            }
        }
    protected Closure $prepareValue;
    protected(set) int $position = 0;
    protected(set) bool $mutable = true;
    public string $placeholder {
        get => str_replace('%%name%%', $this->name, $this->placeHolderMask);
    }
    private string $placeHolderMask = ':%%name%%'
        {
            /**
             * @throws UnexpectedValueException
             */
            set {
                if (!str_contains($value, '%%name%%')) {
                    throw new UnexpectedValueException('El placeholder debe contener el token %%name%%');
                }
                $this->placeHolderMask = $value;
            }
        }

    public function setPlaceholderMask(string $placeholder): void
    {
        $this->placeHolderMask = $placeholder;
    }

    public function hasValue(): bool
    {
        return $this->isInitialized;
    }
}