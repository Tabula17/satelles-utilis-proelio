<?php

namespace Tabula17\Satelles\Utilis\Data;

interface HydratorInterface
{
    public function hydrate(mixed $value): mixed;
}