<?php

namespace Tabula17\Satelles\Utilis\Job;

use Throwable;

interface JobQueueInterface
{
    public function push(AbstractJob $job): string;

    public function pop(?float $timeout = null): ?AbstractJob;

    public function ack(string $jobId): void;

    public function cancel(string $jobId): void;

    public function fail(string $jobId, Throwable $error): void;

    public function retry(string $jobId): void;

    public function storeResult(AbstractJobResult $result): void;

    public function pullResult(string $jobId): ?AbstractJobResult;

    public function getResult(string $jobId): ?AbstractJobResult;

    public function popResult(?float $timeout = null): ?AbstractJobResult;

    public function getFailure(string $jobId): ?Throwable;

    public function stats(): array;

    public function isEmpty(): bool;

    public function clear(): void;

    public function exists(string $jobId): bool;
}