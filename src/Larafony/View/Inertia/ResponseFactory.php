<?php

declare(strict_types=1);

namespace Larafony\Framework\View\Inertia;

use Larafony\Framework\Http\JsonResponse;
use Larafony\Framework\Http\Response;
use Larafony\Framework\View\ViewManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ResponseFactory
{
    public function __construct(
        private readonly ServerRequestInterface $request,
        private readonly ?ViewManager $viewManager = null
    ) {
    }

    /**
     * Create appropriate response based on request type
     *
     * @param array<string, mixed> $page
     * @param string $rootView
     *
     * @return ResponseInterface
     */
    public function createResponse(array $page, string $rootView): ResponseInterface
    {
        // First visit: return full HTML page
        if (! $this->isInertiaRequest()) {
            return $this->htmlResponse($page, $rootView);
        }

        // External redirect detection (409 Conflict)
        if ($this->request->getMethod() === 'GET' && $this->wantsHtml()) {
            return $this->locationResponse($page['url'] ?? '/');
        }

        // Subsequent visits: return JSON
        return $this->jsonResponse($page);
    }

    /**
     * Create HTML response for initial page load
     *
     * @param array<string, mixed> $page
     */
    private function htmlResponse(array $page, string $rootView): ResponseInterface
    {
        if ($this->viewManager === null) {
            throw new \RuntimeException('ViewManager not provided to ResponseFactory');
        }

        $view = $this->viewManager->make($rootView, ['page' => $page]);
        return $view->render();
    }

    /**
     * Create 409 Conflict response for external redirects
     */
    private function locationResponse(string $url): ResponseInterface
    {
        return new Response()
            ->withStatus(409)
            ->withHeader('X-Inertia-Location', $url);
    }

    /**
     * Create JSON response for Inertia requests
     *
     * @param array<string, mixed> $page
     */
    private function jsonResponse(array $page): ResponseInterface
    {
        return new JsonResponse($page)
            ->withHeader('X-Inertia', 'true')
            ->withHeader('Vary', 'X-Inertia');
    }

    /**
     * Check if this is an Inertia request (from Inertia.js client)
     */
    private function isInertiaRequest(): bool
    {
        return $this->request->hasHeader('X-Inertia');
    }

    /**
     * Check if request wants HTML (for external redirect detection)
     */
    private function wantsHtml(): bool
    {
        $accept = $this->request->getHeaderLine('Accept');
        return str_contains($accept, 'text/html');
    }
}
