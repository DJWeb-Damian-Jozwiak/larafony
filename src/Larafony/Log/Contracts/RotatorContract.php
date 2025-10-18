<?php

declare(strict_types=1);

namespace Larafony\Framework\Log\Contracts;

interface RotatorContract
{
    public function shouldRotate(string $logPath): bool;
    public function rotate(string $logPath): string;
    public function cleanup(string $logPath): void;
}
