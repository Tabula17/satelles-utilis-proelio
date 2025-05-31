<?php

namespace Tabula17\Satelles\Utilis\Queue;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Implementación de cola de tareas usando RabbitMQ.
 * 
 * Nota: Algunas funcionalidades de la interfaz QueueInterface no son implementables
 * en RabbitMQ debido a su naturaleza de mensaje-broker y su modelo de consumo de mensajes.
 */
class RabbitMQQueue implements QueueInterface
{
    private AMQPStreamConnection $connection;
    private AMQPChannel $channel;
    private string $queueName;

    public function __construct(array $config)
    {
        $this->connection = new AMQPStreamConnection(
            $config['host'],
            $config['port'],
            $config['user'],
            $config['password']
        );
        $this->channel = $this->connection->channel();
        $this->queueName = $config['queue'] ?? 'default_queue';
        $this->channel->queue_declare($this->queueName, false, true, false, false);
    }

    public function getChannel(): string
    {
        return $this->queueName;
    }

    public function push(array $task): string
    {
        $taskId = uniqid('task_', true);
        $task['_task_id'] = $taskId;
        $msg = new AMQPMessage(json_encode($task), ['delivery_mode' => 2]);
        $this->channel->basic_publish($msg, '', $this->queueName);
        return $taskId;
    }

    /**
     * {@inheritdoc}
     * 
     * Nota: En RabbitMQ, una vez consumido un mensaje, no se puede volver a obtener.
     * El delivery_tag se agrega al mensaje para poder hacer acknowledge posteriormente.
     */
    public function pop(): ?array
    {
        $msg = $this->channel->basic_get($this->queueName);
        if ($msg) {
            $task = json_decode($msg->body, true);
            $task['_delivery_tag'] = $msg->delivery_info['delivery_tag'];
            return $task;
        }
        return null;
    }

    /**
     * {@inheritdoc}
     * 
     * Nota: Este método no puede implementarse completamente en RabbitMQ ya que el acknowledge
     * debe hacerse usando el delivery_tag obtenido al consumir el mensaje, no el taskId.
     * Se recomienda usar el delivery_tag almacenado en el mensaje tras llamar a pop().
     */
    public function ack(string $taskId, string $member = 'tasks'): void
    {
        // No se puede hacer ack por taskId, solo por delivery_tag
        // Se recomienda usar pop() y luego ack usando delivery_tag
        // Este método queda vacío o puede lanzar una excepción si se requiere
    }

    // Métodos de resultados: se pueden almacenar en memoria local o en otro sistema, aquí se usa un array estático simple
    private static array $results = [];

    public function pushResult(string $taskId, array $result): void
    {
        self::$results[$taskId] = $result;
    }

    public function getResult(string $taskId): ?array
    {
        return self::$results[$taskId] ?? null;
    }

    public function clear(): void
    {
        $this->channel->queue_purge($this->queueName);
        self::$results = [];
    }

    public function getPendingCount(): int
    {
        [, $messageCount,] = $this->channel->queue_declare($this->queueName, true);
        return $messageCount;
    }

    public function getTaskCount(): int
    {
        return $this->getPendingCount();
    }

    public function getResultCount(): int
    {
        return count(self::$results);
    }

    /**
     * {@inheritdoc}
     * 
     * Nota: RabbitMQ no proporciona una forma de obtener mensajes sin consumirlos.
     * Este método retorna un array vacío.
     */
    public function getAllTasks(): array
    {
        // No es posible obtener todos los mensajes sin consumirlos en RabbitMQ
        return [];
    }

    public function getAllResults(): array
    {
        return self::$results;
    }

    /**
     * {@inheritdoc}
     * 
     * Nota: RabbitMQ no proporciona una forma de obtener mensajes sin consumirlos.
     * Este método retorna un array vacío.
     */
    public function getPendingTasks(): array
    {
        // No es posible obtener todos los mensajes sin consumirlos en RabbitMQ
        return [];
    }

    /**
     * {@inheritdoc}
     * 
     * Nota: RabbitMQ no proporciona una forma de obtener IDs de mensajes sin consumirlos.
     * Este método retorna un array vacío.
     */
    public function getPendingTaskIds(): array
    {
        // No es posible obtener todos los IDs sin consumir los mensajes
        return [];
    }

    /**
     * {@inheritdoc}
     * 
     * Nota: RabbitMQ no proporciona una forma de buscar mensajes específicos sin consumirlos.
     * Este método siempre retorna null.
     */
    public function getTask(string $taskId): ?array
    {
        // No es posible buscar por taskId en RabbitMQ sin consumir todos los mensajes
        return null;
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }
}
