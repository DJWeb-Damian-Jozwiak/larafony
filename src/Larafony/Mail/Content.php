<?php

declare(strict_types=1);

namespace Larafony\Framework\Mail;

use Larafony\Framework\View\ViewManager;
use Larafony\Framework\Web\Application;

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
        private ?ViewManager $viewManager = null
    ) {
    }

    public function render(): string
    {
        if ($this->viewManager === null) {
            /** @var ViewManager $viewManager */
            $viewManager = Application::instance()->get(ViewManager::class);
            $this->viewManager = $viewManager;
        }

        return $this->viewManager->make($this->view, $this->data)->render();
    }
}
