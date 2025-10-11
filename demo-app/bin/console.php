<?php

declare(strict_types=1);

// Test PHP 8.5 first-class callable in const expression

/**
 * @var \Larafony\Framework\Console\Application $app
 */
$app = require_once __DIR__ . '/../bootstrap/console_app.php';
$app->handle();
