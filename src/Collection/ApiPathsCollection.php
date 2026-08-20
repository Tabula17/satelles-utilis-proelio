<?php

namespace Tabula17\Satelles\Utilis\Collection;

use Tabula17\Satelles\Utilis\Collection\TypedCollection;
use Tabula17\Satelles\Utilis\Config\ApiPathConfig;
use Tabula17\Satelles\Utilis\Config\HttpMethodEnum;
use Throwable;

class ApiPathsCollection extends TypedCollection
{

    protected static function getType(): string
    {
        return ApiPathConfig::class;
    }

    public function findByMethod(string $method)
    {
        return $this->find(fn(ApiPathConfig $config) => $config->method === HttpMethodEnum::fromString($method));
    }

    public function findByName(string $name)
    {
        return $this->find(fn(ApiPathConfig $config) => $config->name === $name);
    }

    public function collect(string $key): array
    {
        return $this->map(fn(ApiPathConfig $config) => $config->{$key});
    }

    public function definedPathNames(): array
    {
        return $this->map(fn(ApiPathConfig $config) => $config->name);
    }

    public static function fromArray(array $config): static
    {
        $values = [];

        foreach ($config as $key => $item) {
            try {
                $values[$key] = static::cast($item);
            } catch (Throwable $e) {
                continue;
            }
        }

        return new static(...$values);
    }
}