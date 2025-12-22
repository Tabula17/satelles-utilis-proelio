<?php

namespace Tabula17\Satelles\Utilis\Connectable;

use Swoole\Server;

/**
 * Class HealthManager
 *
 * This class is responsible for managing health checks across workers in a server ecosystem.
 * It provides mechanisms to start, stop, and monitor health checks, as well as perform periodic diagnostics
 * such as database and memory health inspections. The class also handles graceful stopping of health checks
 * to ensure a clean shutdown process.
 */
interface HealthManagerInterface
{

    /**
     * Inicia el ciclo de health checks para un worker
     */
    public function startHealthCheckCycle(Server $server, int $workerId): void;

    /**
     * Detiene todos los health checks gracefulmente
     */
    public function stopHealthCheckCycle(int $timeout = 5): array;

    /**
     * Ejecuta los health checks manualmente
     *
     */
    public function performHealthChecks(int $workerId = 0, bool $resetFailures = false): array;

    /**
     * Obtiene estado de salud actual
     */
    public function getHealthStatus(): array;

    /**
     * Obtiene estadísticas detalladas por worker
     */
    public function getWorkerStats(): array;

    /**
     * Obtiene historial de checks
     */
    public function getCheckHistory(int $limit = 20): array;

    /**
     * Registers a notifier callback function.
     *
     * @param callable $notifier A callable function to be executed as a notifier.
     * @return void
     */
    public function registerNotifier(callable $notifier): void;
}