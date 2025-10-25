<?php

declare(strict_types=1);

namespace Larafony\Framework\View\Contracts;

interface ComponentContract
{
    public function render(): string;
}
