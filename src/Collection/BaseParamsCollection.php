<?php

namespace Tabula17\Satelles\Utilis\Collection;

use Tabula17\Satelles\Utilis\Config\BaseParamConfig;

class BaseParamsCollection extends TypedCollection
{
    final const string TYPE = BaseParamConfig::class;

    public static string $type = BaseParamConfig::class;

    protected static function getType(): string
    {
        if (!is_a(static::TYPE, BaseParamConfig::class, true)) {
            throw new \InvalidArgumentException(sprintf('The type %s must be an instance of %s', static::TYPE, BaseParamConfig::class));
        }
        return static::TYPE;
    }

    public function findParam(string $name): mixed
    {
        return $this->find(fn(BaseParamConfig $config) => $config->name === $name);
    }

    public function getValue(string $paramName): mixed
    {
        return $this->findParam($paramName)?->value;
    }

    public function sortBy(string $key): static
    {
        return $this->sort(fn(BaseParamConfig $a, BaseParamConfig $b) => $a->$key <=> $b->$key);
    }

    public function getValidParams(): ?self
    {
        return $this->filter(fn(BaseParamConfig $config) => $config->required || $config->hasValue())?->sortBy('position');
    }

    public function getParams(): ?self
    {
        return $this->sortBy('position');
    }

    public function getValues(bool $onlyValid = true): array
    {
        $values = [];
        foreach (($onlyValid ? $this->getValidParams() : $this->getParams())?->extractAll() as $param) {
            $values[$param->name] = $param->value;
        }
        return $values;
    }

    public function getPlaceholders(bool $onlyValid = true): array
    {
        $values = [];
        foreach (($onlyValid ? $this->getValidParams() : $this->getParams())?->extractAll() as $param) {
            $values[$param->name] = $param->placeholder;
        }
        return $values;
    }

    public function setPlaceholderMask(string $mask): void
    {
        foreach ($this->values as $param) {
            $param->setPlaceholderMask($mask);
        }
    }

    public function getRequired(): self
    {
        return $this->filter(fn(BaseParamConfig $config) => $config->required);
    }

    public function getOptional(): self
    {
        return $this->filter(fn(BaseParamConfig $config) => !$config->required);
    }

    public function setValues(array $values): void
    {
        foreach ($values as $name => $value) {
            $param = $this->findParam($name);
            if ($param) {
                $param->set('value', $value);
            }
        }
    }

    public function setValue(string $name, mixed $value): void
    {
        $param = $this->findParam($name);
        if ($param) {
            $param->set('value', $value);
        }
    }

    public static function fromArray(array $config): static
    {
        $values = [];

        foreach ($config as $key => $item) {
          //  echo "Adding param: $key with value: ".var_export($item, true)."\n";
            try {
                $values[$key] = static::cast($item);
            } catch (\Throwable $e) {
                continue;
            }
        }

        return new static(...$values);
    }

}