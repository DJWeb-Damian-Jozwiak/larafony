<?php

declare(strict_types=1);

namespace Larafony\Framework\DebugBar\Middleware;

use Larafony\Framework\Log\Log;
use Larafony\Framework\Web\Application;
use Larafony\Framework\DebugBar\DebugBar;
use Larafony\Framework\View\ViewManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class InjectDebugBar implements MiddlewareInterface
{
    private DebugBar $debugBar;
    public function __construct()
    {
        $this->debugBar = Application::instance()->get(DebugBar::class);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);


        if (! $this->debugBar->isEnabled()) {
            return $response;
        }

        if (! $this->shouldInject($response)) {
            return $response;
        }

        return $this->injectDebugBar($response);
    }

    private function shouldInject(ResponseInterface $response): bool
    {
        $contentType = $response->getHeaderLine('Content-Type');

        if (! str_contains($contentType, 'text/html')) {
            return false;
        }

        if ($response->getStatusCode() >= 400) {
            return false;
        }

        return true;
    }

    private function injectDebugBar(ResponseInterface $response): ResponseInterface
    {
        $body = (string) $response->getBody();

        if (! str_contains($body, '</body>')) {
            return $response;
        }

        $viewManager = Application::instance()->get(ViewManager::class);
        $data = $this->debugBar->collect();

        $debugBarHtml = $viewManager->make('debugbar.toolbar', [
            'data' => $data,
        ])->render()->getBody()->getContents();

        $body = str_replace('</body>', $debugBarHtml . '</body>', $body);

        // Create new response with modified body
        $newBody = $response->getBody();
        $newBody->rewind();
        $newBody->write($body);

        return $response->withBody($newBody);
    }
}
