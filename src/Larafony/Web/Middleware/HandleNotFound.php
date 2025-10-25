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

        $html = <<<HTML
            <!DOCTYPE html>
            <html>
            <head>
                <title>404 Not Found</title>
                <style>
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                        max-width: 600px;
                        margin: 100px auto;
                        padding: 20px;
                        text-align: center;
                    }
                    h1 { color: #e74c3c; font-size: 72px; margin: 0; }
                    h2 { color: #34495e; }
                    a { color: #3498db; text-decoration: none; }
                    a:hover { text-decoration: underline; }
                    code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
                </style>
            </head>
            <body>
                <h1>404</h1>
                <h2>Page Not Found</h2>
                <p>The page <code>{$path}</code> does not exist.</p>
                <p><a href="/">← Go back home</a></p>
            </body>
            </html>
            HTML;

        return $this->responseFactory->createResponse(404)
            ->withContent($html)
            ->withHeader('Content-Type', 'text/html; charset=utf-8');
    }
}
