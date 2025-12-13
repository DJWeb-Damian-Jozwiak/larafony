<?php

declare(strict_types=1);

namespace Larafony\Framework\Mail;

use Larafony\Framework\View\ViewManager;

/**
 * Represents the email content (body).
 */
class Content
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public readonly string $view,
        public readonly array $data = [],
    ) {
    }

    public function render(ViewManager $viewManager): string
    {
        return $viewManager->make($this->view, $this->data)->render()
            ->getBody()->getContents();
    }
}
