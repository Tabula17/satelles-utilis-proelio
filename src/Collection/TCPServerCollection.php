<?php

namespace Tabula17\Satelles\Utilis\Collection;

use Tabula17\Satelles\Utilis\Config\TCPServerConfig;

class TCPServerCollection extends TypedCollection
{
    protected static string $type = TCPServerConfig::class;

    public function __construct(TCPServerConfig...$connections)
    {
        parent::__construct(static::$type, $connections);
    }
    /**
     * @param string $host
     * @return TCPServerConfig|null
     */
    public function findByHost(string $host): TCPServerConfig|null
    {
        return $this->findBy('host', $host);
    }
    public function removeByHost(string $host): void
    {
        $this->removeBy('host', $host);
    }
    public function filterByHost(string $host): self
    {
        return $this->filterBy('host', $host);
    }

    public function findBy(string $key, mixed $value)
    {
        return $this->find(fn(TCPServerConfig $config) => $config->$key === $value);
    }

    public function filterBy(string $key, mixed $value): self
    {
        return $this->filter(fn(TCPServerConfig $config) => $config->$key === $value);
    }
    public function removeBy(string $key, mixed $value): void
    {
        $this->remove($this->findBy($key, $value));
    }
    public function collect(string $key): array
    {
        return array_filter(array_map(static fn(TCPServerConfig $config) => $config->$key, $this->values));
    }
}