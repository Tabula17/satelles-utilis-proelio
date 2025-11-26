<?php

namespace Tabula17\Satelles\Utilis\Collection;

use Tabula17\Satelles\Utilis\Config\ConnectionConfig;

class ConnectionCollection extends GenericCollection
{

    public static string $type = ConnectionConfig::class;

    public function __construct(ConnectionConfig...$connections)
    {
        $this->values = $connections;
    }

    /**
     * @param string $name
     * @return ConnectionConfig|null
     */
    public function findByName(string $name): ConnectionConfig|null
    {
        return $this->findBy('name', $name);
    }
    public function removeByName(string $name): void
    {
        $this->removeBy('name', $name);
    }

    public function filterByHost(string $name): self
    {
        return $this->filterBy('host', $name);
    }

    public function findBy(string $key, mixed $value)
    {
        return $this->find(fn(ConnectionConfig $config) => $config->$key === $value);
    }

    public function filterBy(string $key, mixed $value): self
    {
        return $this->filter(fn(ConnectionConfig $config) => $config->$key === $value);
    }
    public function removeBy(string $key, mixed $value): void
    {
        $this->remove($this->findBy($key, $value));
    }
    public function collect(string $key): array
    {
        return array_filter(array_map(static fn(ConnectionConfig $config) => $config->$key, $this->values));
    }
    public static function fromArray(array $config): self
    {
        return new self(...array_map(static fn($item) => $item instanceof self::$type ? $item : new self::$type($item), $config));
    }
}