<?php

declare(strict_types=1);

namespace Larafony\Framework\Console;

use Larafony\Framework\Console\Contracts\OutputContract;
use Larafony\Framework\Console\Formatters\OutputFormatter;
use Larafony\Framework\Container\Contracts\ContainerContract;

readonly class Output implements OutputContract
{
    protected OutputFormatter $formatter;

    public function __construct(private ContainerContract $container)
    {
        $formatter = $this->container->get(OutputFormatter::class);
        $this->formatter = $formatter;
    }

    public function write(string $message): void
    {
        $outputStream = $this->container->get('output_stream');
        $outputStream->write($this->formatter->format($message));
    }

    public function writeln(string $message): void
    {
        $this->write($message . PHP_EOL);
    }

    public function info(string $message): void
    {
        $this->writeln("<info>{$message}</info>");
    }

    public function warning(string $message): void
    {
        $this->writeln("<warning>{$message}</warning>");
    }

    public function error(string $message): void
    {
        $this->writeln("<danger>{$message}</danger>");
    }

    public function success(string $message): void
    {
        $this->writeln("<success>{$message}</success>");
    }

    public function question(string $text, string $default = ''): string
    {
        $outputStream = $this->container->get('output_stream');
        $inputStream = $this->container->get('input_stream');
        $this->info($text);
        $outputStream->write(PHP_EOL);
        $result = trim($inputStream->read(1024));
        if (! $result) {
            $result = $default;
        }
        return $result;
    }

    public function secret(string $text): string
    {
        $outputStream = $this->container->get('output_stream');
        $inputStream = $this->container->get('input_stream');

        $this->info($text);
        $outputStream->write(PHP_EOL);

        // Check if we're in a TTY (interactive terminal)
        if ($this->isTty()) {
            // Disable echo
            $this->withStty('-echo');

            $value = trim($inputStream->read(1024));

            // Re-enable echo
            $this->withStty('echo');

            // Add newline after hidden input
            $outputStream->write(PHP_EOL);

            return $value;
        }

        // Fallback for non-TTY environments (tests, pipes, etc.)
        return trim($inputStream->read(1024));
    }

    private function isTty(): bool
    {
        return function_exists('posix_isatty') && posix_isatty(STDIN);
    }

    private function withStty(string $mode): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // Windows doesn't support stty, skip
            return;
        }

        // Suppress errors in case stty is not available
        shell_exec("stty {$mode}");
    }

    /**
     * Display a selection menu and return the chosen option
     *
     * @param array<int, string> $options
     * @return string The selected option
     */
    public function select(array $options, string $question = 'Please select an option:'): string
    {
        $outputStream = $this->container->get('output_stream');
        $inputStream = $this->container->get('input_stream');

        $this->info($question);
        $outputStream->write(PHP_EOL);

        foreach ($options as $index => $option) {
            $this->writeln("  [{$index}] {$option}");
        }

        $outputStream->write(PHP_EOL);
        $outputStream->write("<info>Enter your choice: </info>");

        $choice = trim($inputStream->read(1024));

        if (!is_numeric($choice) || !isset($options[(int)$choice])) {
            $this->error("Invalid choice. Please select a valid option.");
            return $this->select($options, $question);
        }

        return $options[(int)$choice];
    }
}
