<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Advanced;

readonly class RouteBinding
{
    public function __construct(
        public string $modelClass,
        public string $findMethod = 'findForRoute',
    ) {
    }
}
