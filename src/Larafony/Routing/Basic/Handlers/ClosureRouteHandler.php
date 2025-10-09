<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Basic\Handlers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ClosureRouteHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly \Closure $handler,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return ($this->handler)($request);
    }
}
