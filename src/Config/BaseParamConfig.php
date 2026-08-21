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
    protected(set) mixed $value
        {
            /**
             * @throws UnexpectedValueException
             * @throws Throwable
             */
            set {
                if (isset($this->prepareValue)) {
                    $value = ($this->prepareValue)($value);
                }
                $this->value = self::cast($value, $this->type, $this->allowNull);
            }
            get {
                if ($this->required || $this->value) {
                    // Si el valor es nulo y no es permitido, retornar el valor por defecto. Si no hay valor por defecto, lanzar excepción si allowNull es falso.
                    return $this->value ?? self::cast($this->defaultValue, $this->type, $this->allowNull);
                }
                return $this->value;
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
}