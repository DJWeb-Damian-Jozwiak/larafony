<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Basic;

use Larafony\Framework\Routing\Basic\Factories\ArrayHandlerFactory;
use Larafony\Framework\Routing\Basic\Factories\StringHandlerFactory;
use Larafony\Framework\Routing\Basic\Handlers\ClosureRouteHandler;
use Psr\Http\Server\RequestHandlerInterface;

final class RouteHandlerFactory
{
    public function __construct(
        private readonly ArrayHandlerFactory $arrayFactory,
        private readonly StringHandlerFactory $stringFactory,
    ) {
    }

    /**
     * @param \Closure|array{class-string, string}|string $handler
     */
    public function create(\Closure|array|string $handler): RequestHandlerInterface
    {
        return match (true) {
            $handler instanceof \Closure => $this->createClosureHandler($handler),
            is_array($handler) => $this->arrayFactory->create($handler),
            is_string($handler) => $this->stringFactory->create($handler),
        };
    }

    private function createClosureHandler(\Closure $handler): ClosureRouteHandler
    {
        return new ClosureRouteHandler($handler);
    }
}
