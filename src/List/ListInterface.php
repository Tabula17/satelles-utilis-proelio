<?php

namespace Tabula17\Satelles\Utilis\List;

interface ListInterface
{
    public function add(string $key, string $value): void;

    public function get(string $key): array;

    public function clear(string $key): void;

    public function clearAll(): void;

    public function getCount(string $key): int;

    public function getKeys(): array;

    public function pop(string $key): string|false;
}