<?php

declare(strict_types=1);

use Larafony\Framework\Web\Application;

$app = Application::instance();

return [
    'engines' => [
        'blade' => [
            'paths' => [
                'template_path' => $app->base_path . '/resources/views',
                'cache_path' => $app->storage_path . '/cache/views',
            ],
            'components' => [
                'namespace' => '\\App\\View\\Components',
            ],
        ],
    ],
];
