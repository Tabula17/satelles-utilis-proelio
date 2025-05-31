<?php

namespace Tabula17\Satelles\Utilis\Queue;

use Redis;

/**
 * Implementación de cola de tareas usando Redis.
 */
class RedisQueue implements QueueInterface
{
    private Redis $redis;
    private string $channel;

    /**
     * Constructor.
     *
     * @param string $host
     * @param int $port
     * @param string|null $channel
     * @param float $timeout
     */
    public function __construct(string $host, int $port, ?string $channel = null, float $timeout = 2.5)
    {
        $this->redis = new Redis();
        $this->redis->connect(
            $host,
            $port,
            $timeout// timeout
        );
        $this->channel = ($channel ?? 'default') . ':';
        $this->redis->setOption(Redis::OPT_PREFIX, $this->channel);
    }

    /** {@inheritdoc} */
    public function getChannel(): string
    {
        return $this->channel;
    }

    // El resto de los métodos heredan la documentación de la interfaz
    // No es necesario repetir los PHPDoc cuando la implementación es directa

    public function push(array $task): string
    {
        $taskId = uniqid('task_', true);
        $this->redis->hSet("queue:tasks", $taskId, json_encode($task));
        $this->redis->rPush("queue:pending", $taskId);
        return $taskId;
    }

    public function pop(): ?array
    {
        $taskId = $this->redis->lPop("queue:pending");
        if (!$taskId) {
            return null;
        }

        $task = $this->redis->hGet("queue:tasks", $taskId);
        return $task ? json_decode($task, true) : null;
    }

    public function ack(string $taskId, string $member = 'tasks'): void
    {
        $this->redis->hDel("queue:$member", $taskId);
    }

    public function pushResult(string $taskId, array $result): void
    {
        $this->redis->hSet(
            "queue:results",
            $taskId,
            json_encode($result)
        );
    }
    public function getResult(string $taskId): ?array
    {
        $result = $this->redis->hGet("queue:results", $taskId);
        return $result ? json_decode($result, true) : null;
    }
    public function clear(): void
    {
        $this->redis->del("queue:tasks");
        $this->redis->del("queue:pending");
        $this->redis->del("queue:results");
    }
    public function getPendingCount(): int
    {
        return $this->redis->lLen("queue:pending");
    }
    public function getTaskCount(): int
    {
        return $this->redis->hLen("queue:tasks");
    }
    public function getResultCount(): int
    {
        return $this->redis->hLen("queue:results");
    }
    public function getAllTasks(): array
    {
        $tasks = $this->redis->hGetAll("queue:tasks");
        return array_map(fn($task) => json_decode($task, true), $tasks);
    }
    public function getAllResults(): array
    {
        $results = $this->redis->hGetAll("queue:results");
        return array_map(fn($result) => json_decode($result, true), $results);
    }
    public function getPendingTasks(): array
    {
        $pending = $this->redis->lRange("queue:pending", 0, -1);
        return array_map(fn($taskId) => $this->redis->hGet("queue:tasks", $taskId), $pending);
    }
    public function getPendingTaskIds(): array
    {
        return $this->redis->lRange("queue:pending", 0, -1);
    }
    public function getTask(string $taskId): ?array
    {
        $task = $this->redis->hGet("queue:tasks", $taskId);
        return $task ? json_decode($task, true) : null;
    }

}
