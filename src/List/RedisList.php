<?php

namespace Tabula17\Satelles\Utilis\List;

use Redis;
use Tabula17\Satelles\Utilis\Config\RedisConfig;

class RedisList implements ListInterface
{
    private Redis $redis;

    public function __construct(
        RedisConfig             $redisConfig,
        private readonly string $list = 'roga-index',
        string                  $prefix = 'satelles:',
        null|int                $serializer = Redis::SERIALIZER_PHP
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

    public function getListName(): string
    {
        return $this->list;
    }

    public function add(string $value): void
    {
        $this->redis->rpush($this->list, $value);
    }

    public function get(): array
    {
        return $this->redis->lrange($this->list, 0, -1);
    }

    public function clear(): void
    {
        $this->redis->del($this->list);
    }

    public function clearAll(): void
    {
        $this->redis->flushDB();
    }

    public function getCount(): int
    {
        return $this->redis->llen($this->list);
    }

    public function getKeys(): array
    {
        return $this->redis->keys('*');
    }

    public function pop(): string|false
    {
        return $this->redis->lpop($this->list);
    }

    public function __destruct()
    {
        $this->redis->close();
    }

}