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

    private string $verboseIcon = '🛰️';

    /**
     * Formats a message for console output with a timestamp and class name.
     *
     * @param string|\Stringable $message The message to format.
     * @param array $context Additional context to include in the message.
     * @return string The formatted message.
     */
    private function formatConsoleMessage(string|\Stringable $message, array $context = []): string
    {
        $formatted = date('[Y-m-d H:i:s]') . " [" . str_replace(__NAMESPACE__ . '\\', '', __CLASS__) . "] " . $message;
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
    private function log(int $level, string|\Stringable $message, array $context = []): void
    {
        $levels = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];
        if ($this->isVerbose($level)) {
            $icon = $this->verboseIcon . match ($levels[$level] ?? 'info') {
                    'debug' => '🐞',
                    'notice' => '🔔',
                    'warning', 'alert' => '⚠️',
                    'error' => '❌',
                    'critical', 'emergency' => '🚨',
                    default => 'ℹ️'
                };
            echo $this->formatConsoleMessage("$icon $level: " . $message, $context);
        }
    }

    private function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log(0, $message, $context);
    }

    private function info(string|\Stringable $message, array $context = []): void
    {
        $this->log(1, $message, $context);
    }

    private function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log(2, $message, $context);
    }

    private function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log(3, $message, $context);
    }

    private function error(string|\Stringable $message, array $context = []): void
    {
        $this->log(4, $message, $context);
    }

    private function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log(5, $message, $context);
    }

    private function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log(6, $message, $context);
    }

    private function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log(7, $message, $context);
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