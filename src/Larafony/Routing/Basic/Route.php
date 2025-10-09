<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Basic;

use Larafony\Framework\Http\Enums\HttpMethod;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Route implements RequestHandlerInterface
{
    private readonly RequestHandlerInterface $handler;

    /**
     * @param \Closure|array{class-string, string}|string $handlerDefinition
     */
    public function __construct(
        public string $path,
        public HttpMethod $method,
        \Closure|array|string $handlerDefinition,
        RouteHandlerFactory $factory,
        public ?string $name = null,
    ) {
        $this->handler = $factory->create($handlerDefinition);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->handler->handle($request);
    }
}
