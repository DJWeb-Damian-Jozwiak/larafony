<?php

declare(strict_types=1);

namespace Larafony\Framework\View;

use Larafony\Framework\View\Contracts\AssetManagerContract;

class AssetManager implements AssetManagerContract
{
    /**
     * @var array<string, array<int, string>>
     */
    private array $stacks = [];

    public function push(string $stack, string $content): void
    {
        if (! isset($this->stacks[$stack])) {
            $this->stacks[$stack] = [];
        }
        $this->stacks[$stack][] = $content;
    }

    public function render(string $stack): string
    {
        return implode("\n", $this->stacks[$stack] ?? []);
    }
}
