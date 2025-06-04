<?php

namespace Tabula17\Satelles\Utilis\Console;

use Psr\Log\LoggerInterface;

class Log implements LoggerInterface
{
    use VerboseTrait {
        isVerbose as private;
        log as public log;
        notice as public notice;
        warning as public warning;
        error as public error;
        critical as public critical;
        alert as public alert;
        emergency as public emergency;
        debug as public debug;
        info as public info;

    }


    public function __construct(private readonly int $verboseLevel = 4, private readonly bool $useEnv = false, string $verboseIcon = '📺')
    {
        $this->verboseIcon = $verboseIcon;
    }
    private function isVerbose(int $level): bool
    {

        return $level >= $this->verboseLevel;
    }
}