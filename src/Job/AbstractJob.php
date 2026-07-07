<?php

namespace Tabula17\Satelles\Utilis\Job;

use Tabula17\Satelles\Utilis\Config\AbstractDescriptor;

abstract class AbstractJob extends AbstractDescriptor implements JobInterface
{
    //public readonly string $jobId;
    protected(set) string $jobId {
        set {
            if (isset($this->jobId)) {
                throw new \Error("Cannot modify initialized property AbstractJob::\$jobId");
            }
            $this->jobId = $value;
        }
    }
    //abstract public static function fromArray(array $data): static;
}