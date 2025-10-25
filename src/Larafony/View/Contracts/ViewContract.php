<?php

declare(strict_types=1);

namespace Larafony\Framework\View\Contracts;

use Psr\Http\Message\ResponseInterface;

interface ViewContract
{
    public function render(): ResponseInterface;
    public function with(string $key, mixed $value): self;
}
