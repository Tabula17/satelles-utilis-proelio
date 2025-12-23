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

    public function add(mixed $value): void
    {
        $this->redis->rpush($this->list, $value);
    }

    public function get(): array
    {
        return $this->redis->lrange($this->list, 0, -1);
    }

    public function tail(int $limit): false|array
    {
        return $this->redis->lrange($this->list, -$limit, -1);
    }

    public function head(int $limit): false|array
    {
        return $this->redis->lrange($this->list, 0, $limit);
    }

    public function remove(mixed $value): int
    {
        return $this->redis->lrem($this->list, 0, $value);
    }

    public function removeAt(int $index): void
    {
        $this->redis->lrem($this->list, 0, $this->redis->lindex($this->list, $index));
    }

    public function trim(int $start, int $stop): void
    {
        $this->redis->ltrim($this->list, $start, $stop);
    }

    public function contains(mixed $value): bool
    {
        return $this->redis->lpos($this->list, $value) !== false;
    }

    public function isEmpty(): bool
    {
        return $this->redis->llen($this->list) === 0;
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

    public function pop(): Redis|string|array|bool
    {
        return $this->redis->lpop($this->list);
    }

    public function __destruct()
    {
        $this->redis->close();
    }

}