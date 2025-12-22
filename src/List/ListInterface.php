<?php

namespace Tabula17\Satelles\Utilis\List;

interface ListInterface
{
    public function getListName(): string;
    public function add(mixed $value): void;

    public function get(): array;

    public function clear(): void;

    public function clearAll(): void;

    public function getCount(): int;

    public function getKeys(): array;

    public function pop(): string|false;
}