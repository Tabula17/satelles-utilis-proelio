<?php

namespace Tabula17\Satelles\Utilis\Console;

use Psr\Log\LoggerInterface;

class Log implements LoggerInterface
{

    use VerboseTrait {
        isVerbose as private;
        log as public;
        notice as public;
        warning as public;
        error as public;
        critical as public;
        alert as public;
        emergency as public;
        debug as public;
        info as public;

    }


    public function __construct(private readonly int $verboseLevel = 4, private readonly bool $useEnv = false, string $verboseContext = 'LOG', string $verboseIcon = '📺')
    {
        $this->verboseIcon = $verboseIcon;
        $this->verboseContext = $verboseContext;
    }

    private function isVerbose(int $level): bool
    {

        return $level >= $this->verboseLevel;
    }
}