<?php

declare(strict_types=1);

namespace Larafony\Framework\Events\View;

final readonly class ViewRendering
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public string $view,
        public array $data,
    ) {
    }
}
