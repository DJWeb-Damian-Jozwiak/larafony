<?php

declare(strict_types=1);

namespace Larafony\Framework\Events\View;

final readonly class ViewRendered
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public string $view,
        public array $data,
        public string $content,
        public float $renderTime,
    ) {
    }
}
