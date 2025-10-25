<?php

declare(strict_types=1);

use Larafony\Framework\Routing\Middleware\RouterMiddleware;
use Larafony\Framework\Web\Middleware\HandleNotFound;

return [
    'before_global' => [
        HandleNotFound::class,
    ],
    'global' => [
        RouterMiddleware::class,
    ],
    'after_global' => [],
];
