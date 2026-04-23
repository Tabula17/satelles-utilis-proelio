<?php

namespace Tabula17\Satelles\Utilis\Job\Worker;

use Tabula17\Satelles\Utilis\Job\AbstractJob;
use Tabula17\Satelles\Utilis\Job\AbstractJobResult;

interface JobWorkerInterface
{
    public function start(int $workers = 1): void;

    public function stop(): void;

    public function isRunning(): bool;

    public function process(AbstractJob $job): AbstractJobResult;
}