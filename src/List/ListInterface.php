<?php

namespace Tabula17\Satelles\Utilis\List;

interface ListInterface
{
    public function getListName(): string;
    public function add(mixed $value): void;

    public function get(): array;
    public function tail(int $limit): false|array;
    public function head(int $limit): false|array;
    public function remove(mixed $value): int;
    public function removeAt(int $index): void;
    public function trim(int $start, int $stop): void;
    public function contains(mixed $value): bool;
    public function isEmpty(): bool;
    public function clear(): void;

    public function clearAll(): void;

    public function getCount(): int;

    public function getKeys(): array;

    public function pop(): string|false;
}