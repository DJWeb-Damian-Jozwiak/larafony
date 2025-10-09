<?php

declare(strict_types=1);

// Test PHP 8.5 first-class callable in const expression

/**
 * @var \Larafony\Framework\Web\Application $app
 */
$app = require_once __DIR__ . '/../bootstrap/web_app.php';
$app->run();
