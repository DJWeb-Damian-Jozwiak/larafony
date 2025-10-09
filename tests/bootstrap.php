<?php

declare(strict_types=1);

// Load composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Restore default PHP error handlers for tests
restore_error_handler();
restore_exception_handler();
