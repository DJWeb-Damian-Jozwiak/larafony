<?php

declare(strict_types=1);

namespace App\View\Components;

use Larafony\Framework\View\Component;

class Alert extends Component
{
    protected function getView(): string
    {
        return 'components.Alert';
    }
}
