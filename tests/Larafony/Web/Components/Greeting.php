<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Web\Components;

use Larafony\Framework\View\Component;

class Greeting extends Component
{
    public function __construct(
        public string $name = 'Guest'
    ) {
    }

    protected function getView(): string
    {
        return 'components.greeting';
    }
}
