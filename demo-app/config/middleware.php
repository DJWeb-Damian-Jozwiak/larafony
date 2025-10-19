<?php

declare(strict_types=1);

use Larafony\Framework\Routing\Middleware\RouterMiddleware;

return [
    'before_global' => [ ],
    'global' => [
        RouterMiddleware::class,
    ],
    'after_global' => [],
];
