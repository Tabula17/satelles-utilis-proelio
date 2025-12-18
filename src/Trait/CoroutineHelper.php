<?php

namespace Tabula17\Satelles\Utilis\Trait;

use Swoole\Coroutine;

trait CoroutineHelper
{
    /**
     * Determines if the current execution is within a coroutine context.
     *
     * @return bool True if running inside a coroutine, otherwise false.
     */
    public function isInCoroutine(): bool
    {
        return extension_loaded('swoole') && Coroutine::getCid() > 0;
    }

    /**
     * Sleep seguro que funciona tanto en corutinas como fuera de ellas
     */
    public function safeSleep(float $seconds): void
    {
        if ($this->isInCoroutine()) {
            Coroutine::sleep($seconds);// En corutina, usar Coroutine::sleep
        } else if ($seconds < 1) {
            usleep((int)($seconds * 1000000));// Para segundos fraccionales
        } else {
            sleep((int)$seconds); // Para segundos enteros
        }
    }

    /**
     * Ejecuta código en corutina si es posible, sino ejecuta normalmente
     * Ejemplo de uso:
     *  $result = $this->runInCoroutineIfPossible(
     *      function($param1, $param2) {
     *      // Tu lógica aquí
     *      Coroutine::sleep(0.1); // Seguro dentro de coroutine
     *          return $param1 + $param2;
     *      }, 10, 20
     *  );
     */
    public function runInCoroutineIfPossible(callable $callback, ...$args)
    {
        if ($this->isInCoroutine()) {
            // Ya estamos en corutina, ejecutar directamente
            return $callback(...$args);
        }
        // Crear nueva corutina
        return Coroutine::create($callback, ...$args);
    }

    /**
     * Retrieve detailed information about the current coroutine environment and context.
     *
     * @return array An associative array containing information such as:
     *               - 'current_cid': The ID of the current coroutine.
     *               - 'parent_cid': The ID of the parent coroutine.
     *               - 'all_coroutines': A list of all coroutine IDs.
     *               - 'context': The context of a specific coroutine.
     *               - 'stats': General coroutine statistics.
     *               - 'backtrace': A backtrace of the current coroutine.
     *               - 'status': The execution status (e.g., 'running' or 'out_of_coroutine').
     */
    public function getCoroutineInfo(): array
    {
        return [
            // Obtener ID de la corutina actual
            'current_cid' => Coroutine::getCid(),
            // Obtener ID de la corutina padre
            'parent_cid' => Coroutine::getPcid(),
            // Listar TODAS las corutinas (devuelve array de IDs)
            'all_coroutines' => Coroutine::list(),
            // Obtener el contexto de una corutina específica
            'context' => Coroutine::getContext(),
            // Obtener estadísticas generales (no por corutina individual)
            'stats' => Coroutine::stats(),
            // Backtrace
            'backtrace' => Coroutine::getBackTrace(Coroutine::getCid(), DEBUG_BACKTRACE_PROVIDE_OBJECT, 10),
            // Obtener el estado de ejecución (solo para corutina actual)
            'status' => $this->isInCoroutine() ? 'running' : 'out_of_coroutine',
        ];
    }
}