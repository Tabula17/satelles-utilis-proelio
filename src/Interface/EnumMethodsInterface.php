<?php

namespace Tabula17\Satelles\Utilis\Interface;

interface EnumMethodsInterface extends \UnitEnum
{
    public static function fromValue(mixed $value): ?static;

}