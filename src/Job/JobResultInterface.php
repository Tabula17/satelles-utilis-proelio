<?php

namespace Tabula17\Satelles\Utilis\Job;

use JsonSerializable;

interface JobResultInterface extends JsonSerializable
{
    /**
     * Validates the current state or input based on predefined criteria. Throws an exception if the validation fails.
     * Implement this method to define specific validation rules for the job result. 100% of the time, you should call the parent method.
     * @throws \Throwable
     *
     * @return void
     */
    public function validate(): void;

}