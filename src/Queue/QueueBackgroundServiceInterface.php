<?php

namespace Tabula17\Satelles\Utilis\Queue;

interface QueueBackgroundServiceInterface
{
    public function start(): void;

    public function stop(): void;

    public function isRunning(): bool;

}