<?php

namespace Tabula17\Satelles\Utilis\List;

use Redis;
use Tabula17\Satelles\Utilis\Config\RedisConfig;

class RedisList implements ListInterface
{
    private Redis $redis;

    public function __construct(
        RedisConfig $redisConfig,
        string      $prefix = 'roga-list:',
        null|int    $serializer = Redis::SERIALIZER_PHP
    )
    {
        $this->redis = new Redis(array_filter($redisConfig->toArray()));
        if (!empty($prefix)) {
            if (!str_ends_with($prefix, ':')) {
                $prefix .= ':';
            }
            $this->redis->setOption(Redis::OPT_PREFIX, $prefix);
        }
        if ($serializer !== null) {
            $this->redis->setOption(Redis::OPT_SERIALIZER, $serializer);
        }
    }
    public function add(string $key, string $value): void
    {
        $this->redis->rpush($key, $value);
    }
    public function get(string $key): array
    {
        return $this->redis->lrange($key, 0, -1);
    }
    public function clear(string $key): void
    {
        $this->redis->del($key);
    }
    public function clearAll(): void
    {
        $this->redis->flushDB();
    }
    public function getCount(string $key): int
    {
        return $this->redis->llen($key);
    }
    public function getKeys(): array
    {
        return $this->redis->keys('*');
    }
    public function pop(string $key): string|false
    {
        return $this->redis->lpop($key);
    }
    public function __destruct()
    {
        $this->redis->close();
    }

}