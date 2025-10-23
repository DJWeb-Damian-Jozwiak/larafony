# Chapter 14: PSR-3 Logging System

> Part of the Larafony Framework - A modern PHP 8.5 framework built from scratch
>
> 📚 Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)

## Overview

Chapter 14 introduces a comprehensive, PSR-3 compliant logging system built from the ground up. This implementation showcases modern PHP 8.5 features including readonly classes, property hooks, and the pipe operator, while maintaining strict adherence to PSR-3 standards for maximum interoperability.

The logging system features a flexible handler architecture supporting multiple output formats (text, JSON, XML), destinations (file, database), and automatic log rotation with configurable retention policies. The design emphasizes separation of concerns with dedicated classes for formatting, handling, context management, and placeholder processing.

Unlike traditional logging libraries that rely on heavy dependencies, Larafony's logger is lightweight and purpose-built, leveraging PHP 8.5's native capabilities to provide a clean, testable API that integrates seamlessly with the framework's service container while remaining fully compatible with any PSR-3 consumer.

## Key Components

### Core Logging

- **Logger** - PSR-3 compliant logger implementation using LoggerTrait with support for multiple handlers and placeholder processing (src/Larafony/Log/Logger.php:13)
- **Log** - Static facade providing convenient access to all PSR-3 log levels (emergency, alert, critical, error, warning, notice, info, debug) via the application container (src/Larafony/Log/Log.php:10)
- **LoggerFactory** - Factory for creating logger instances with configured handlers based on application configuration (src/Larafony/Log/LoggerFactory.php:17)

### Message Components

- **Message** - Readonly value object encapsulating log level, message text, context, and metadata (src/Larafony/Log/Message.php:9)
- **Context** - Manages contextual data passed with log messages, providing type-safe access to context values (src/Larafony/Log/Context.php:7)
- **Metadata** - Captures automatic metadata like timestamps using the framework's PSR-20 clock implementation (src/Larafony/Log/Metadata.php:10)
- **PlaceholderProcessor** - Processes PSR-3 message placeholders (e.g., `{username}`) by replacing them with context values (src/Larafony/Log/PlaceholderProcessor.php:7)

### Handlers

Handler system for writing logs to different destinations:
- **FileHandler** - Writes formatted logs to files with automatic directory creation and rotation support (src/Larafony/Log/Handlers/FileHandler.php:13)
- **DatabaseHandler** - Persists logs to database using the framework's ORM (src/Larafony/Log/Handlers/DatabaseHandler.php:9)

### Formatters

Formatting system for converting log messages to different output formats:
- **TextFormatter** - Human-readable text format with timestamp, level, message, context, and metadata (src/Larafony/Log/Formatters/TextFormatter.php:11)
- **JsonFormatter** - JSON format with pretty printing for structured logging and log aggregation tools (src/Larafony/Log/Formatters/JsonFormatter.php:10)
- **XmlFormatter** - XML format for systems requiring XML-based log ingestion (src/Larafony/Log/Formatters/XmlFormatter.php:11)

### Log Rotation

- **DailyRotator** - Implements daily log rotation with configurable retention period and automatic cleanup of old log files using PHP 8.5 pipe operator (src/Larafony/Log/Rotators/DailyRotator.php:11)

### Supporting Infrastructure

- **LogLevel** - Backed enum mapping PSR-3 log level constants to strongly-typed values (src/Larafony/Enums/Log/LogLevel.php:9)
- **DatabaseLog** - ORM entity for storing log entries in database (src/Larafony/DBAL/Models/Entities/DatabaseLog.php:10)
- **LoggerError** - Exception thrown for logger configuration and runtime errors (src/Larafony/Exceptions/Log/LoggerError.php:7)

## PSR Standards Implemented

- **PSR-3**: Logger Interface - Full compliance with LoggerInterface including all eight log levels and the LoggerTrait. The Message class supports Stringable per PSR-3 requirements, and placeholder processing follows the PSR-3 specification for context interpolation.

## Usage Examples

### Basic Logging

```php
<?php

use Larafony\Framework\Log\Log;

// Using the static facade for simple logging
Log::info('User logged in', ['user_id' => 123, 'ip' => '192.168.1.1']);

Log::error('Database connection failed', [
    'database' => 'production',
    'error' => $exception->getMessage()
]);

Log::debug('Cache miss', ['key' => 'user:123:profile']);

// All PSR-3 log levels are supported
Log::emergency('System is down');
Log::alert('Disk space critical');
Log::critical('Application crashed');
Log::warning('High memory usage detected');
Log::notice('Configuration updated');
```

### Placeholder Processing

```php
<?php

use Larafony\Framework\Log\Log;

// PSR-3 placeholder syntax
Log::info('User {username} performed {action}', [
    'username' => 'john.doe',
    'action' => 'logout'
]);
// Outputs: "User john.doe performed logout"

// Supports arrays, booleans, and Stringable objects
Log::warning('Failed login attempt from {ip} with data {data}', [
    'ip' => '10.0.0.1',
    'data' => ['username' => 'admin', 'attempts' => 5]
]);
```

### Advanced Configuration

```php
<?php

use Larafony\Framework\Log\Logger;
use Larafony\Framework\Log\Handlers\FileHandler;
use Larafony\Framework\Log\Handlers\DatabaseHandler;
use Larafony\Framework\Log\Formatters\JsonFormatter;
use Larafony\Framework\Log\Formatters\TextFormatter;
use Larafony\Framework\Log\Rotators\DailyRotator;

// Create logger with multiple handlers
$logger = new Logger([
    // File handler with JSON formatting and daily rotation
    new FileHandler(
        logPath: '/var/log/app/application.log',
        formatter: new JsonFormatter(),
        rotator: new DailyRotator(maxDays: 30)
    ),

    // Another file handler with text formatting
    new FileHandler(
        logPath: '/var/log/app/debug.log',
        formatter: new TextFormatter(),
        rotator: new DailyRotator(maxDays: 7)
    ),

    // Database handler for critical logs
    new DatabaseHandler()
]);

// Use the logger
$logger->info('Application started', ['version' => '2.0.0']);
$logger->error('Unexpected error', ['exception' => $e]);
```

### Factory-Based Configuration

```php
<?php

use Larafony\Framework\Log\LoggerFactory;
use Larafony\Framework\Web\Application;
use Psr\Log\LoggerInterface;

// Configuration in config/logging.php
return [
    'channels' => [
        [
            'handler' => 'file',
            'path' => '/var/log/app/app.log',
            'formatter' => 'json',
            'max_days' => 14
        ],
        [
            'handler' => 'database'
        ]
    ]
];

// Create logger from configuration
$logger = LoggerFactory::create();

// Register in container
Application::instance()->set(LoggerInterface::class, $logger);

// Use via facade anywhere in the application
Log::info('Configured logger ready');
```

### Custom Handler Implementation

```php
<?php

use Larafony\Framework\Log\Contracts\HandlerContract;
use Larafony\Framework\Log\Message;

class SlackHandler implements HandlerContract
{
    public function __construct(
        private readonly string $webhookUrl,
        private readonly string $channel
    ) {}

    public function handle(Message $message): void
    {
        // Only send critical logs to Slack
        if ($message->level->value !== 'critical') {
            return;
        }

        $payload = json_encode([
            'channel' => $this->channel,
            'text' => $message->message,
            'attachments' => [
                [
                    'color' => 'danger',
                    'fields' => [
                        ['title' => 'Level', 'value' => $message->level->value],
                        ['title' => 'Context', 'value' => json_encode($message->context->all())]
                    ]
                ]
            ]
        ]);

        // Send to Slack webhook
        // ... HTTP client implementation
    }
}

// Use custom handler
$logger = new Logger([
    new FileHandler('/var/log/app.log', new TextFormatter()),
    new SlackHandler('https://hooks.slack.com/...', '#alerts')
]);
```

## Comparison with Other Frameworks

| Feature | Larafony | Laravel | Symfony |
|---------|----------|---------|---------|
| PSR-3 Compliance | Native implementation with LoggerTrait | Via Monolog wrapper | Via Monolog integration |
| Dependencies | Zero external logging dependencies | Monolog required | Monolog bundle required |
| Configuration | Programmatic + factory with config array | YAML/PHP config files with channels | YAML config with channels and handlers |
| Handlers | Custom lightweight implementations | Monolog's 50+ handlers | Monolog's 50+ handlers |
| Formatters | Built-in Text/JSON/XML | Monolog formatters | Monolog formatters |
| Log Rotation | Native DailyRotator with pipe operator | Via Monolog RotatingFileHandler | Via Monolog rotating handler |
| Placeholder Processing | Custom PlaceholderProcessor | Monolog's message processing | Monolog's processors |
| Multiple Destinations | Multiple handlers via array | Stack channel aggregation | Multiple handlers via config |
| Database Logging | Native with ORM entities | Via Monolog database handler | Via Doctrine handler |
| Approach | Lightweight, attribute-ready, PHP 8.5 | Configuration-driven, Monolog facade | Service-based, dependency injection |

**Key Differences:**

- **Zero Dependencies**: Larafony implements PSR-3 directly without external logging libraries, reducing the dependency footprint. Laravel and Symfony both rely on Monolog, a mature but heavy logging library with many features most applications don't need.

- **Modern PHP 8.5 Features**: Larafony leverages readonly classes, property hooks, backed enums, and the pipe operator (in DailyRotator) for cleaner, more performant code. Laravel and Symfony support older PHP versions and can't utilize these cutting-edge features.

- **Explicit Handler Architecture**: Handlers are explicitly passed to the Logger constructor, making the data flow transparent. Laravel's "channels" abstraction and Symfony's configuration-based approach hide the handler setup behind configuration files.

- **First-Class Types**: Using LogLevel enum and readonly Message/Context/Metadata classes provides compile-time type safety. Monolog-based solutions use arrays and string constants for levels.

- **Lightweight Formatters**: Three purpose-built formatters cover most use cases without the complexity of Monolog's 20+ formatter classes. Custom formatters implement a simple FormatterContract interface.

- **ORM Integration**: DatabaseHandler integrates directly with Larafony's ORM entities, while Laravel/Symfony require separate Monolog database handler configuration.

- **Educational Value**: The implementation demonstrates how to build a production-ready logging system from scratch, teaching PSR-3 compliance, handler patterns, and message processing—knowledge that transfers to understanding any PSR-3 logger.

## Larafony with Monolog? Simple as a Piece of Cake

Thanks to full PSR-3 compliance, integrating Monolog is trivial. Both Larafony's Logger and Monolog implement `Psr\Log\LoggerInterface`, making them completely interchangeable. Here's how to swap implementations via configuration:

### Step 1: Install Monolog

```bash
composer require monolog/monolog
```

### Step 2: Create Configuration

```php
<?php
// config/logging.php

return [
    // Choose your logger implementation: 'larafony' or 'monolog'
    'driver' => env('LOG_DRIVER', 'larafony'),

    'larafony' => [
        'channels' => [
            [
                'handler' => 'file',
                'path' => storage_path('logs/app.log'),
                'formatter' => 'json',
                'max_days' => 14
            ],
            [
                'handler' => 'database'
            ]
        ]
    ],

    'monolog' => [
        'name' => 'larafony-app',
        'handlers' => [
            [
                'type' => 'stream',
                'path' => storage_path('logs/app.log'),
                'level' => 'debug'
            ],
            [
                'type' => 'rotating',
                'path' => storage_path('logs/daily.log'),
                'max_files' => 14,
                'level' => 'info'
            ]
        ]
    ]
];
```

### Step 3: Create Monolog Factory

```php
<?php

namespace Larafony\Framework\Log;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Larafony\Framework\Web\Config;
use Psr\Log\LoggerInterface;

class MonologFactory
{
    public static function create(): LoggerInterface
    {
        $config = Config::get('logging.monolog');
        $logger = new MonologLogger($config['name']);

        foreach ($config['handlers'] as $handlerConfig) {
            $handler = match ($handlerConfig['type']) {
                'stream' => new StreamHandler(
                    $handlerConfig['path'],
                    $handlerConfig['level'] ?? 'debug'
                ),
                'rotating' => new RotatingFileHandler(
                    $handlerConfig['path'],
                    $handlerConfig['max_files'] ?? 7,
                    $handlerConfig['level'] ?? 'debug'
                ),
                default => throw new \InvalidArgumentException(
                    "Unknown Monolog handler: {$handlerConfig['type']}"
                )
            };

            $logger->pushHandler($handler);
        }

        return $logger;
    }
}
```

### Step 4: Register in Service Provider

```php
<?php

namespace App\Providers;

use Larafony\Framework\Log\LoggerFactory;
use Larafony\Framework\Log\MonologFactory;
use Larafony\Framework\Web\Application;
use Larafony\Framework\Web\Config;
use Psr\Log\LoggerInterface;

class LoggingServiceProvider
{
    public function register(Application $app): void
    {
        $app->singleton(LoggerInterface::class, function () {
            $driver = Config::get('logging.driver', 'larafony');

            return match ($driver) {
                'monolog' => MonologFactory::create(),
                'larafony' => LoggerFactory::create(),
                default => throw new \InvalidArgumentException(
                    "Unknown logging driver: {$driver}"
                )
            };
        });
    }
}
```

### Step 5: Use Anywhere in Your Application

```php
<?php

use Larafony\Framework\Log\Log;

// The exact same API works with both implementations!
Log::info('User logged in', ['user_id' => 123]);
Log::error('Database error', ['exception' => $e->getMessage()]);
Log::debug('Cache hit', ['key' => 'user:profile:123']);

// Your application code doesn't know or care whether it's using
// Larafony's Logger or Monolog - that's the power of PSR-3!
```

### Switching Between Implementations

Simply change the environment variable:

```bash
# .env

# Use Larafony's lightweight logger
LOG_DRIVER=larafony

# Or switch to Monolog with 50+ handlers
LOG_DRIVER=monolog
```

**The rest of your application requires zero changes.** This is the power of coding to interfaces (PSR-3) rather than implementations—a core principle of SOLID design and dependency inversion.

---

📚 **Learn More:** This implementation is explained in detail with step-by-step tutorials, tests, and best practices at [masterphp.eu](https://masterphp.eu)
