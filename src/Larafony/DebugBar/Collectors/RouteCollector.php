<?php

declare(strict_types=1);

namespace Larafony\Framework\DebugBar\Collectors;

use Larafony\Framework\DebugBar\Contracts\DataCollectorContract;
use Larafony\Framework\Events\Attributes\Listen;
use Larafony\Framework\Events\Routing\RouteMatched;

final class RouteCollector implements DataCollectorContract
{
    private ?RouteMatched $matchedRoute = null;

    #[Listen]
    public function onRouteMatched(RouteMatched $event): void
    {
        $this->matchedRoute = $event;
    }

    public function collect(): array
    {
        if ($this->matchedRoute === null) {
            return [];
        }

        $route = $this->matchedRoute->route;

        return [
            'uri' => $route->path,
            'name' => $route->name,
            'method' => $route->method->value,
            'action' => $route->action,
            'parameters' => $this->matchedRoute->parameters,
        ];
    }

    public function getName(): string
    {
        return 'route';
    }

    private function formatAction(mixed $action): string
    {
    }
}
