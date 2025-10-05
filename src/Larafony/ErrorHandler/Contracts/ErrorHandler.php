<?php

declare(strict_types=1);

namespace Larafony\Framework\ErrorHandler\Contracts;

use Throwable;

interface ErrorHandler
{
    public function handle(Throwable $throwable): void;
    public function register(): void;
}