<?php

declare(strict_types=1);

namespace App\View\Components;

use Larafony\Framework\View\Component;

class StatusBadge extends Component
{
    public function __construct(
        public readonly string $status = 'info',
        public readonly bool $active = true,
    ) {
    }

    protected function getView(): string
    {
        return 'components.StatusBadge';
    }
}
