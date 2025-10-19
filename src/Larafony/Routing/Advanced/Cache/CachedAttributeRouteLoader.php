<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Advanced\Cache;

use Larafony\Framework\Routing\Advanced\AttributeRouteLoader;
use Larafony\Framework\Routing\Advanced\AttributeRouteScanner;
use Larafony\Framework\Routing\Advanced\Route;
use Larafony\Framework\Routing\Basic\RouteHandlerFactory;

readonly class CachedAttributeRouteLoader extends AttributeRouteLoader
{
    public function __construct(
        AttributeRouteScanner $scanner,
        RouteHandlerFactory $handlerFactory,
        private RouteCache $cache,
        private bool $enableCache = true,
    ) {
        parent::__construct($scanner, $handlerFactory);
    }

    /**
     * @return array<int, Route>
     */
    public function loadFromDirectory(string $path): array
    {
        if (! $this->enableCache) {
            return parent::loadFromDirectory($path);
        }

        $cached = $this->cache->get($path);
        if ($cached !== null) {
            return $cached;
        }

        $routes = parent::loadFromDirectory($path);
        $this->cache->put($path, $routes);

        return $routes;
    }
}
