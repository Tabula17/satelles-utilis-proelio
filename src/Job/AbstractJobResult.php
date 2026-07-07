<?php

namespace Tabula17\Satelles\Utilis\Job;

use Tabula17\Satelles\Utilis\Config\AbstractDescriptor;

abstract class AbstractJobResult extends AbstractDescriptor implements JobResultInterface
{
    //public readonly string $jobId;
    protected(set) string $jobId {
        set {
            if (isset($this->jobId)) {
                throw new \Error("Cannot modify initialized property AbstractJobResult::\$jobId");
            }
            $this->jobId = $value;
        }
    }

}