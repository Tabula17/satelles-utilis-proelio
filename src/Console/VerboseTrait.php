<?php

namespace Tabula17\Satelles\Utilis\Console;

/**
 * Trait VerboseTrait
 *
 * Provides methods for verbose console output.
 * This trait is intended to be used in classes that require verbose logging capabilities.
 */
trait VerboseTrait
{

    public const DEBUG = 100;
    public const INFO = 200;
    public const NOTICE = 250;
    public const WARNING = 300;
    public const ERROR = 400;
    public const CRITICAL = 500;
    public const ALERT = 550;
    public const EMERGENCY = 600;
    public const VERBOSE_LEVELS = [
        self::DEBUG => 'debug',
        self::INFO => 'info',
        self::NOTICE => 'notice',
        self::WARNING => 'warning',
        self::ERROR => 'error',
        self::CRITICAL => 'critical',
        self::ALERT => 'alert',
        self::EMERGENCY => 'emergency',
    ];


    private string $verboseIcon = '🛰️';
    private ?string $verboseContext = null;

    /**
     * Formats a message for console output with a timestamp and class name.
     *
     * @param string|\Stringable $message The message to format.
     * @param array $context Additional context to include in the message.
     * @return string The formatted message.
     */
    private function formatConsoleMessage(string|\Stringable $message, array $context = []): string
    {
        $icon = $this->verboseIcon??'';
        $contextLog= $this->verboseContext ?? str_replace(__NAMESPACE__ . '\\', '', __CLASS__);

        $formatted = "{$icon}}[{$contextLog}]" . date('[Y-m-d H:i:s]') . " " . $message;
        if (!empty($context)) {
            $formatted .= ' ' . json_encode($context);
        }
        return $formatted . PHP_EOL;
    }

    /**
     * Outputs a message to the console if verbose mode is enabled.
     *
     * @param string|\Stringable $message The message to output.
     * @param array $context Additional context to include in the message.
     */
    private function log(mixed $level, string|\Stringable $message, array $context = []): void
    {
        $level = (int)$level;

        if ($this->isVerbose($level)) {
            $icon = match (self::VERBOSE_LEVELS[$level] ?? 'info') {
                'debug' => '🐞',
                'notice' => '🔔',
                'warning', 'alert' => '⚠️',
                'error' => '❌',
                'critical', 'emergency' => '🚨',
                default => 'ℹ️'
            };
            echo $this->formatConsoleMessage("$icon: " . $message, $context);
        }
    }

    private function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log(self::DEBUG, $message, $context);
    }

    private function info(string|\Stringable $message, array $context = []): void
    {
        $this->log(self::INFO, $message, $context);
    }

    private function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log(self::NOTICE, $message, $context);
    }

    private function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log(self::WARNING, $message, $context);
    }

    private function error(string|\Stringable $message, array $context = []): void
    {
        $this->log(self::ERROR, $message, $context);
    }

    private function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log(self::CRITICAL, $message, $context);
    }

    private function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log(self::ALERT, $message, $context);
    }

    private function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log(self::EMERGENCY, $message, $context);
    }

    /**
     * Checks if verbose mode is enabled.
     *
     * This method should be implemented in the class using this trait to determine
     * whether verbose output should be displayed.
     *
     * @return bool True if verbose mode is enabled, false otherwise.
     */
    abstract private function isVerbose(int $level): bool;
}