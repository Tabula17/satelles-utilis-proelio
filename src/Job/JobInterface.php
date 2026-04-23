<?php

namespace Tabula17\Satelles\Utilis\Job;

use JsonSerializable;

interface JobInterface extends JsonSerializable
{
    public function markQueued(): void;

    public function markRunning(): void;

    public function markCompleted(): void;

    public function markFailed(): void;

    public function markRetrying(): void;

    public function cancel(): void;

    public function canRetry(): bool;

    public function withPriority(int $priority): static;

    public function withMaxAttempts(int $maxAttempts): static;

    public function getStatus(): mixed;
    public function validate(): void;
}