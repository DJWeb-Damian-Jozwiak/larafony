<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Middleware;

use Larafony\Framework\Routing\Advanced\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

readonly class RouterMiddleware implements MiddlewareInterface
{
    public function __construct(private Router $router)
    {
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     *
     * @codeCoverageIgnore
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $this->router->handle($request);
        } catch (\Throwable $exception) {
            $request = $request->withAttribute('exception', $exception);
            return $handler->handle($request);
        }
    }
}
