<?php

namespace Tabula17\Satelles\Utilis\Job;

use JsonSerializable;

interface JobResultInterface extends JsonSerializable
{
    public function validate(): void;

}