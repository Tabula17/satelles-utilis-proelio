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

    /**
     * Formats a message for console output with a timestamp and class name.
     *
     * @param string $message The message to format.
     * @param array $context Additional context to include in the message.
     * @return string The formatted message.
     */
    private function formatConsoleMessage(string $message, array $context = []): string
    {
        $formatted = date('[Y-m-d H:i:s]') . "🛰️ [" . str_replace(__NAMESPACE__ . '\\', '', __CLASS__) . "] " . $message;
        if (!empty($context)) {
            $formatted .= ' ' . json_encode($context);
        }
        return $formatted . PHP_EOL;
    }
    /**
     * Outputs a message to the console if verbose mode is enabled.
     *
     * @param string $message The message to output.
     * @param array $context Additional context to include in the message.
     */
    private function console(string $message, array $context = []): void
    {
        if ($this->isVerbose()) {
            echo $this->formatConsoleMessage($message, $context);
        }
    }
    abstract private function isVerbose(): bool;
}