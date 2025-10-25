<?php

declare(strict_types=1);

namespace Larafony\Framework\View\Contracts;

interface AssetManagerContract
{
    public function push(string $stack, string $content): void;
    public function render(string $stack): string;
}
