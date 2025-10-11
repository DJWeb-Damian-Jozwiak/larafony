<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Contracts;

interface OutputContract
{
    public function write(string $message): void;

    public function writeln(string $message): void;

    public function info(string $message): void;

    public function warning(string $message): void;

    public function error(string $message): void;

    public function success(string $message): void;

    public function question(string $text): string;
}
