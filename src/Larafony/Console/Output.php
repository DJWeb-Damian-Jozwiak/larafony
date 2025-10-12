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

    public function question(string $text): string
    {
        $outputStream = $this->container->get('output_stream');
        $inputStream = $this->container->get('input_stream');
        $this->info($text);
        $outputStream->write(PHP_EOL);
        return trim($inputStream->read(1024));
    }
}
