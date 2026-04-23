<?php

namespace Tabula17\Satelles\Utilis\Job\Service;

use Tabula17\Satelles\Utilis\Job\AbstractJob;
use Tabula17\Satelles\Utilis\Job\AbstractJobResult;
use Throwable;

interface JobManagerInterface
{
    public function start(int $workers = 1): void;

    public function stop(): void;

    public function submit(AbstractJob $job): string;

    public function getResult(string $jobId): ?AbstractJobResult;

    public function waitForResult(string $jobId, int $timeoutSeconds = 30, int $pollIntervalMs = 200): ?AbstractJobResult;

    public function processJob(AbstractJob $job): ?AbstractJobResult;

    public function hasResult(string $jobId): bool;

    public function hasFailure(string $jobId): bool;

    public function getFailure(string $jobId): ?Throwable;

    public function cancelJob(string $jobId): void;

    public function jobExists(string $jobId): bool;

    public function stats(): array;
}