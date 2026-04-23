<?php

namespace Tabula17\Satelles\Utilis\Job;

use Tabula17\Satelles\Utilis\Config\AbstractDescriptor;

abstract class AbstractJobResult extends AbstractDescriptor implements JobResultInterface
{
    public readonly string $jobId;

}