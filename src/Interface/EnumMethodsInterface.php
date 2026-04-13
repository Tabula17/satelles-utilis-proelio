<?php

namespace Tabula17\Satelles\Utilis\Interface;

interface EnumMethodsInterface extends \UnitEnum
{
    public static function fromValue(mixed $value): ?static;
    public static function isValid(mixed $value): bool;

}