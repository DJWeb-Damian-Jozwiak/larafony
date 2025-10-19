<?php



namespace Larafony\Framework\Routing\Advanced\Attributes;

use Attribute;
use Larafony\Framework\Routing\Advanced\Route;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Middleware
{
    /**
     * @param array<int, string> $beforeGlobal
     * @param array<int, string> $afterGlobal
     * @param array<int, string> $withoutMiddleware
     */
    public function __construct(
        private array $beforeGlobal = [],
        private array $afterGlobal = [],
        private array $withoutMiddleware = []
    )
    {
    }

    public function addToRoute(Route $route): void
    {
        array_walk($this->beforeGlobal, $route->withMiddlewareBefore(...));
        array_walk($this->afterGlobal, $route->withMiddlewareAfter(...));
        array_walk($this->withoutMiddleware, $route->withoutMiddleware(...));
    }
}