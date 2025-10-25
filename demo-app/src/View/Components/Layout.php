<?php

declare(strict_types=1);

namespace App\View\Components;

use Larafony\Framework\View\Component;

class Layout extends Component
{
    public function __construct(
        public readonly string $title = 'Larafony Framework',
    ) {
    }

    protected function getView(): string
    {
        return 'components.Layout';
    }
}
