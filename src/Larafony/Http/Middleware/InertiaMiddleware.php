<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Middleware;

use Larafony\Framework\View\Inertia\Inertia;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Inertia.js Middleware
 *
 * Handles Inertia.js specific request/response logic:
 * - Sets up shared props
 * - Handles redirects for PUT/PATCH/DELETE
 */
class InertiaMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Inertia $inertia
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Share data globally (can be overridden in app)
        $this->share($request);

        // Handle the request
        $response = $handler->handle($request);

        // Handle redirects after non-GET requests
        if ($this->isInertiaRequest($request)) {
            $response = $this->handleRedirects($request, $response);
        }

        return $response;
    }

    /**
     * Share data with all Inertia responses
     * Override this method in your application to share global data
     */
    protected function share(ServerRequestInterface $request): void
    {
        $data = $this->getSharedData($request);
        array_walk($data, fn ($value, $key) => $this->inertia->share($key, $value));
    }

    /**
     * Get data to share globally
     * Override this in your application
     *
     * @return array<string, mixed>
     */
    protected function getSharedData(ServerRequestInterface $request): array
    {
        return [
            // Example: 'auth' => fn() => Auth::user(),
            // Example: 'flash' => fn() => Session::get('flash'),
        ];
    }

    /**
     * Handle Inertia-specific redirect logic
     */
    private function handleRedirects(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        // For PUT, PATCH, DELETE requests that result in a redirect,
        // we need to change 302 to 303 to force GET on redirect
        if (
            in_array($request->getMethod(), ['PUT', 'PATCH', 'DELETE'], true)
            && $response->getStatusCode() === 302
        ) {
            return $response->withStatus(303);
        }

        return $response;
    }

    /**
     * Check if this is an Inertia request
     */
    private function isInertiaRequest(ServerRequestInterface $request): bool
    {
        return $request->hasHeader('X-Inertia');
    }
}
