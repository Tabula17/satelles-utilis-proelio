<?php

namespace Tabula17\Satelles\Utilis\Queue;

/**
 * Interface para la implementación de sistemas de cola de tareas.
 */
interface QueueInterface
{
    /**
     * Obtiene el nombre del canal o cola actual.
     *
     * @return string Identificador del canal
     */
    public function getChannel(): string;

    /**
     * Agrega una nueva tarea a la cola.
     *
     * @param array $task Datos de la tarea a encolar
     * @return string Identificador único de la tarea (taskId)
     */
    public function push(array $task): string;

    /**
     * Obtiene y elimina la siguiente tarea de la cola.
     *
     * @return array|null Datos de la tarea o null si no hay tareas pendientes
     */
    public function pop(): ?array;

    /**
     * Confirma el procesamiento de una tarea.
     *
     * @param string $taskId Identificador de la tarea
     * @param string $member Tipo de tarea (por defecto 'tasks')
     */
    public function ack(string $taskId, string $member = 'tasks'): void;

    /**
     * Almacena el resultado de una tarea procesada.
     *
     * @param string $taskId Identificador de la tarea
     * @param array $result Resultado de la tarea
     */
    public function pushResult(string $taskId, array $result): void;

    /**
     * Obtiene el resultado de una tarea específica.
     *
     * @param string $taskId Identificador de la tarea
     * @return array|null Resultado de la tarea o null si no existe
     */
    public function getResult(string $taskId): ?array;

    /**
     * Limpia todas las tareas y resultados de la cola.
     */
    public function clear(): void;

    /**
     * Obtiene el número de tareas pendientes en la cola.
     *
     * @return int Cantidad de tareas pendientes
     */
    public function getPendingCount(): int;

    /**
     * Obtiene el número total de tareas (procesadas y pendientes).
     *
     * @return int Cantidad total de tareas
     */
    public function getTaskCount(): int;

    /**
     * Obtiene el número total de resultados almacenados.
     *
     * @return int Cantidad de resultados
     */
    public function getResultCount(): int;

    /**
     * Obtiene todas las tareas existentes.
     *
     * @return array Lista de todas las tareas
     */
    public function getAllTasks(): array;

    /**
     * Obtiene todos los resultados almacenados.
     *
     * @return array Lista de todos los resultados
     */
    public function getAllResults(): array;

    /**
     * Obtiene la lista de tareas pendientes.
     *
     * @return array Lista de tareas pendientes
     */
    public function getPendingTasks(): array;

    /**
     * Obtiene la lista de identificadores de tareas pendientes.
     *
     * @return array Lista de taskIds pendientes
     */
    public function getPendingTaskIds(): array;

    /**
     * Obtiene una tarea específica por su identificador.
     *
     * @param string $taskId Identificador de la tarea
     * @return array|null Datos de la tarea o null si no existe
     */
    public function getTask(string $taskId): ?array;
}
