<?php

declare(strict_types=1);

namespace Larafony\Framework\Web\Middleware;

use Larafony\Framework\Core\Exceptions\NotFoundError;
use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\View\ViewManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class HandleNotFound implements MiddlewareInterface
{
    public function __construct(
        private readonly ViewManager $viewManager,
        private readonly ResponseFactory $responseFactory,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (NotFoundError $e) {
            // Try to render custom 404 view, fallback to simple HTML
            try {
                return $this->viewManager->make('errors.404', [
                    'path' => $request->getUri()->getPath(),
                ])->render()->withStatus(404);
            } catch (\Throwable) {
                // Fallback if view doesn't exist
                return $this->renderFallback404($request);
            }
        }
    }

    private function renderFallback404(ServerRequestInterface $request): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $path = htmlspecialchars($path, ENT_QUOTES, 'UTF-8');
        $html = '<h1>404 - Page Not Found</h1><p>The page ' . $path . ' does not exist.</p>';

        $response = $this->responseFactory->createResponse(404);
        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }
}
