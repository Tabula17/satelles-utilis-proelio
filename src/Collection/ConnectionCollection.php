<?php

namespace Tabula17\Satelles\Utilis\Collection;

use Tabula17\Satelles\Utilis\Config\ConnectionConfig;

/**
 * Represents a collection of ConnectionConfig objects with utility methods for finding, filtering,
 * and removing configurations based on specific criteria. This class ensures that only
 * ConnectionConfig objects are added to the collection.
 */

class ConnectionCollection extends TypedCollection
{
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

    protected static function getType(): string
    {
        return ConnectionConfig::class;
    }
}