<?php

namespace Tabula17\Satelles\Utilis\Cache;

use Memcached;

/**
 * MemcachedStorage is a cache storage implementation that uses the Memcached extension
 * to manage cache data. It provides methods to interact with a Memcached instance for
 * storing, retrieving, deleting, and clearing cached data.
 */
class MemcachedStorage implements CacheManagerInterface
{
    private(set) Memcached $memcached;

    public function __construct(?string $persistent_id = 'roga-cache', array $servers = ['127.0.0.1:11211'])
    {
        $this->memcached = new Memcached($persistent_id);
        foreach ($servers as $server) {
            $this->memcached->addServer(...explode(':', $server, 3)); //host:port:weight //unix.socket:0:weight *weight is optional
        }
    }

    public function get(string $key, bool $unserialize = true)
    {
        return $this->memcached->get($key);
    }

    public function set(string $key, mixed $value, $serialize = true): void
    {
        $this->memcached->set($key, $value);
    }

    public function has(string $key): bool
    {
        $keys = $this->memcached->getAllKeys();
        if ($keys === false) {
            return false;
        }
        return in_array($key, $keys, true);
    }

    public function delete(string $key): void
    {
        $this->memcached->delete($key);
    }

    public function clear(): void
    {
        $this->memcached->flush();
    }
}