<?php

namespace Tabula17\Satelles\Utilis\Collection;

use Tabula17\Satelles\Utilis\Collection\GenericCollection;
use Tabula17\Satelles\Utilis\Config\ConnectionConfig;

class ConnectionCollection extends GenericCollection
{

    public function __construct(ConnectionConfig...$connections)
    {
        $this->values = $connections;
    }
}