<?php

namespace Tabula17\Satelles\Utilis\Trait;

use Swoole\Coroutine;

trait CoroutineHelper
{
    public function isInCoroutine(): bool
    {
        return Coroutine::getCid() > 0;
    }
    /**
     * Sleep seguro que funciona tanto en corutinas como fuera de ellas
     */
    public function safeSleep(float $seconds): void
    {
        if ($this->isInCoroutine()) {
            // En corutina, usar Coroutine::sleep
            Coroutine::sleep($seconds);
        } else {
            // Fuera de corutina, usar usleep o sleep normal
            if ($seconds < 1) {
                // Para segundos fraccionales
                usleep((int)($seconds * 1000000));
            } else {
                // Para segundos enteros
                sleep((int)$seconds);
            }
        }
    }
    /**
     * Ejecuta código en corutina si es posible, sino ejecuta normalmente
     * // Ejemplo de uso:
     *  $result = $this->runInCoroutineIfPossible(
     *      function($param1, $param2) {
     *      // Tu lógica aquí
     *      Coroutine::sleep(0.1); // Seguro dentro de coroutine
     *      return $param1 + $param2;
     *      },
     *      10, 20
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
}