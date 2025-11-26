<?php

namespace Tabula17\Satelles\Utilis\Collection;

use Tabula17\Satelles\Utilis\Collection\GenericCollection;
use Tabula17\Satelles\Utilis\Config\ConnectionConfig;

class ConnectionCollection extends GenericCollection
{

    public static string $type = ConnectionConfig::class;

    public function __construct(ConnectionConfig...$connections)
    {
        $this->values = $connections;
    }

    public static function fromArray(array $config): self
    {
        return new self(...array_map(static fn($item) => $item instanceof self::$type ? $item : new self::$type($item), $config));
    }
}