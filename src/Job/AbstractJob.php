<?php

namespace Tabula17\Satelles\Utilis\Job;

use Tabula17\Satelles\Utilis\Config\AbstractDescriptor;

abstract class AbstractJob extends AbstractDescriptor implements JobInterface
{
    public readonly string $jobId;
    abstract public static function fromArray(array $data): static;
}