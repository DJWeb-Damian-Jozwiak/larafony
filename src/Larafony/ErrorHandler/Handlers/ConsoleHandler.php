<?php

declare(strict_types=1);

namespace Larafony\Framework\ErrorHandler\Handlers;

use Larafony\Framework\ErrorHandler\BaseHandler;
use Larafony\Framework\ErrorHandler\Renderers\ConsoleRenderer;
use Throwable;

class ConsoleHandler extends BaseHandler
{
    public function __construct(private ConsoleRenderer $renderer, private \Closure $output)
    {
    }

    public function handleException(Throwable $exception): void
    {
        try {
            ($this->output)($this->renderer->render($exception));
        } catch (Throwable) {
            ($this->output)('Critical error occurred. Please check error logs.');
        }
    }
}
