<?php

namespace Tabula17\Satelles\Utilis\Cache;

interface CacheManagerInterface
{
    /**
     * Retrieves the value associated with the specified key.
     *
     * @param string $key The key for which the value is to be retrieved.
     */
    public function get(string $key);

    /**
     * Stores a value associated with the specified key.
     *
     * @param string $key The key to associate with the given value.
     * @param mixed $value The value to be stored.
     * @param int|null $ttl The time-to-live (TTL) for the stored value, in seconds.
     */
    public function set(string $key, mixed $value, ?int $ttl = null);

    /**
     * Checks if the specified key exists.
     *
     * @param string $key The key to check for existence.
     */
    public function has(string $key): bool;

    /**
     * Deletes the value associated with the specified key.
     *
     * @param string $key The key for which the associated value is to be deleted.
     */
    public function delete(string $key);

    /**
     * Clears all entries or data from the current instance.
     */
    public function clear();


}