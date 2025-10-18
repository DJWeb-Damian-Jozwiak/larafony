# Chapter 14: Logging System (PSR-3)

## Overview

In this chapter, we implemented a complete **Logging System** that follows PSR-3 standards, providing flexible, production-ready logging with multiple handlers, formatters, and automatic log rotation. The implementation allows structured logging with context, metadata, and placeholder interpolation.

## Core Philosophy: Structured Logging

The logging system treats logs as **structured data with context**, not just strings:

```php
// Simple logging
Log::info('User logged in');

// With context
Log::warning('Failed login attempt', ['username' => 'john', 'ip' => '192.168.1.1']);

// With placeholder interpolation
Log::error('User {username} failed to access {resource}', [
    'username' => 'john',
    'resource' => '/admin/users'
]);
// Output: "User john failed to access /admin/users"
```

### Why Structured Logging Matters

**Traditional logging:**
```php
error_log("User john failed login from 192.168.1.1");
// Hard to search, parse, or analyze
```

**Structured logging:**
```php
Log::warning('Failed login attempt', [
    'username' => 'john',
    'ip' => '192.168.1.1',
    'user_agent' => 'Mozilla/5.0...'
]);
// Searchable, parseable, analyzable
```

## Architecture

### System Components

```
Logging System
├── Facade Layer
│   └── Log (static facade)
│
├── Core Components
│   ├── Logger (PSR-3 implementation)
│   ├── Message (immutable log message)
│   ├── Context (structured context data)
│   ├── Metadata (automatic metadata)
│   └── PlaceholderProcessor (interpolation)
│
├── Handlers (where to log)
│   ├── FileHandler (log to files)
│   └── DatabaseHandler (log to database)
│
├── Formatters (how to format)
│   ├── TextFormatter (human-readable)
│   ├── JsonFormatter (structured JSON)
│   └── XmlFormatter (XML format)
│
└── Rotators (when to rotate)
    └── DailyRotator (daily rotation + cleanup)
```

## PSR-3 Compliance

The system fully implements **PSR-3 (Logger Interface)**:

```php
interface LoggerInterface
{
    public function emergency($message, array $context = []);
    public function alert($message, array $context = []);
    public function critical($message, array $context = []);
    public function error($message, array $context = []);
    public function warning($message, array $context = []);
    public function notice($message, array $context = []);
    public function info($message, array $context = []);
    public function debug($message, array $context = []);
    public function log($level, $message, array $context = []);
}
```

### Log Levels (RFC 5424)

| Level | Code | Usage |
|-------|------|-------|
| EMERGENCY | 0 | System is unusable |
| ALERT | 1 | Action must be taken immediately |
| CRITICAL | 2 | Critical conditions |
| ERROR | 3 | Runtime errors |
| WARNING | 4 | Warning conditions |
| NOTICE | 5 | Normal but significant condition |
| INFO | 6 | Informational messages |
| DEBUG | 7 | Debug-level messages |

## Usage Examples

### Basic Logging

```php
use Larafony\Framework\Log\Log;

// Different log levels
Log::emergency('Database connection lost');
Log::alert('Disk space critically low');
Log::critical('Application crashed');
Log::error('Failed to process payment');
Log::warning('Slow query detected');
Log::notice('User registered');
Log::info('Cache cleared');
Log::debug('Query executed in 15ms');
```

### Logging with Context

```php
// Simple context
Log::info('Order created', ['order_id' => 12345]);

// Rich context
Log::error('Payment failed', [
    'order_id' => 12345,
    'user_id' => 67,
    'amount' => 99.99,
    'gateway' => 'stripe',
    'error_code' => 'card_declined'
]);

// Exception logging
try {
    $payment->process();
} catch (\Exception $e) {
    Log::error('Payment processing failed', [
        'exception' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'order_id' => $order->id
    ]);
}
```

### Placeholder Interpolation

```php
// PSR-3 placeholder format
Log::info('User {username} logged in from {ip}', [
    'username' => 'john',
    'ip' => '192.168.1.1'
]);
// Output: "User john logged in from 192.168.1.1"

// Multiple placeholders
Log::warning('Failed to send {count} emails to {domain}', [
    'count' => 5,
    'domain' => 'example.com'
]);
// Output: "Failed to send 5 emails to example.com"
```

## Handlers

Handlers determine **where** logs are written.

### FileHandler

Writes logs to files with automatic rotation:

```php
use Larafony\Framework\Log\Handlers\FileHandler;
use Larafony\Framework\Log\Formatters\TextFormatter;
use Larafony\Framework\Log\Rotators\DailyRotator;

$handler = new FileHandler(
    logPath: '/var/www/storage/logs/app.log',
    formatter: new TextFormatter(),
    rotator: new DailyRotator(maxDays: 7)
);
```

**Features:**
- ✅ Automatic file creation
- ✅ Daily rotation
- ✅ Old log cleanup
- ✅ Customizable formatters
- ✅ Configurable retention

### DatabaseHandler

Writes logs to database table:

```php
use Larafony\Framework\Log\Handlers\DatabaseHandler;

$handler = new DatabaseHandler();
// Automatically uses JsonFormatter
// Stores in 'logs' table via DatabaseLog model
```

**Features:**
- ✅ Structured storage
- ✅ Easy querying
- ✅ Full-text search
- ✅ Automatic JSON formatting

## Formatters

Formatters determine **how** logs are formatted.

### TextFormatter

Human-readable format:

```php
use Larafony\Framework\Log\Formatters\TextFormatter;

$formatter = new TextFormatter();

// Output:
// [2024-01-15 10:30:45] INFO: User logged in {"user_id":123}
```

**Best for:**
- ✅ Development
- ✅ Console output
- ✅ Human reading
- ✅ Debugging

### JsonFormatter

Structured JSON format:

```php
use Larafony\Framework\Log\Formatters\JsonFormatter;

$formatter = new JsonFormatter();

// Output:
// {"level":"INFO","message":"User logged in","context":{"user_id":123},"metadata":{"timestamp":"2024-01-15 10:30:45"}}
```

**Best for:**
- ✅ Log aggregation
- ✅ Machine parsing
- ✅ Database storage
- ✅ Log analysis tools

### XmlFormatter

XML format:

```php
use Larafony\Framework\Log\Formatters\XmlFormatter;

$formatter = new XmlFormatter();

// Output:
// <log>
//   <level>INFO</level>
//   <message>User logged in</message>
//   <context>
//     <user_id>123</user_id>
//   </context>
//   <metadata>
//     <timestamp>2024-01-15 10:30:45</timestamp>
//   </metadata>
// </log>
```

**Best for:**
- ✅ Legacy systems
- ✅ XML-based log tools
- ✅ SOAP integrations

## Log Rotation

Automatic log rotation prevents disk space issues.

### DailyRotator

Rotates logs daily and cleans up old files:

```php
use Larafony\Framework\Log\Rotators\DailyRotator;

// Rotate daily, keep 7 days
$rotator = new DailyRotator(maxDays: 7);

// Rotate daily, keep 30 days
$rotator = new DailyRotator(maxDays: 30);

// Custom pattern
$rotator = new DailyRotator(
    maxDays: 14,
    pattern: '/^app-\d{4}-\d{2}-\d{2}\.log$/'
);
```

**How it works:**
1. Checks if current log file is from today
2. If not, renames it: `app.log` → `app-2024-01-14.log`
3. Creates new `app.log` for today
4. Deletes logs older than `maxDays`

**File naming:**
```
storage/logs/
├── app.log              (today)
├── app-2024-01-18.log   (yesterday)
├── app-2024-01-17.log   (2 days ago)
├── app-2024-01-16.log   (3 days ago)
└── ...
```

## Logger Configuration

### Single Handler

```php
use Larafony\Framework\Log\Logger;
use Larafony\Framework\Log\Handlers\FileHandler;

$logger = new Logger([
    new FileHandler('/var/www/storage/logs/app.log')
]);
```

### Multiple Handlers

```php
use Larafony\Framework\Log\Logger;
use Larafony\Framework\Log\Handlers\FileHandler;
use Larafony\Framework\Log\Handlers\DatabaseHandler;

$logger = new Logger([
    new FileHandler('/var/www/storage/logs/app.log'),
    new DatabaseHandler()
]);

// Logs to BOTH file and database
$logger->info('User logged in', ['user_id' => 123]);
```

### Different Formatters per Handler

```php
$logger = new Logger([
    // Text format for console/debugging
    new FileHandler(
        '/var/www/storage/logs/debug.log',
        new TextFormatter()
    ),

    // JSON format for log aggregation
    new FileHandler(
        '/var/www/storage/logs/structured.log',
        new JsonFormatter()
    ),

    // Database for querying
    new DatabaseHandler()
]);
```

## Log Facade

Static facade for convenient access:

```php
use Larafony\Framework\Log\Log;

// Automatically uses configured logger from container
Log::info('Application started');
Log::error('Something went wrong', ['error' => $exception]);

// No need to inject LoggerInterface everywhere!
```

**How it works:**
```php
// Log facade resolves logger from container
private static function logger(): LoggerInterface
{
    return self::$logger ??= Application::instance()
        ->get(LoggerInterface::class);
}
```

## Message Structure

Every log message is an immutable object:

```php
class Message
{
    public function __construct(
        public readonly LogLevel $level,
        public readonly string $message,
        public readonly Context $context,
        public readonly Metadata $metadata
    ) {}
}
```

### Context

User-provided structured data:

```php
$context = new Context([
    'user_id' => 123,
    'action' => 'login',
    'ip' => '192.168.1.1'
]);
```

### Metadata

Automatically added information:

```php
class Metadata
{
    public static function create(): self
    {
        return new self([
            'timestamp' => ClockFactory::now()->format('Y-m-d H:i:s'),
            'memory' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ]);
    }
}
```

## Comparison with Other Frameworks

### Laravel

**Laravel (Monolog):**
```php
use Illuminate\Support\Facades\Log;

Log::info('User logged in', ['user_id' => 123]);

// Configuration in config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'slack'],
    ],
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => 'debug',
        'days' => 14,
    ],
]
```

**Larafony:**
```php
use Larafony\Framework\Log\Log;

Log::info('User logged in', ['user_id' => 123]);

// Configuration in code
$logger = new Logger([
    new FileHandler('/var/www/storage/logs/app.log',
        rotator: new DailyRotator(maxDays: 14)
    )
]);
```

### Key Differences

| Feature | Laravel (Monolog) | Larafony |
|---------|------------------|----------|
| PSR-3 | ✅ Via Monolog | ✅ Native |
| Configuration | Config files | Code-based |
| Dependencies | Monolog package | Built from scratch |
| Handlers | Monolog handlers | Custom handlers |
| Formatters | Monolog formatters | Custom formatters |
| Rotation | Built-in | DailyRotator |
| Type Safety | Partial | Full (strict types) |
| Customization | Via Monolog | Direct control |

### Symfony

**Symfony (Monolog):**
```php
use Psr\Log\LoggerInterface;

class SomeService
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function doSomething(): void
    {
        $this->logger->info('User logged in', ['user_id' => 123]);
    }
}
```

**Larafony (both styles work):**
```php
use Larafony\Framework\Log\Log;

// Static facade (Laravel-style)
Log::info('User logged in', ['user_id' => 123]);

// Dependency injection (Symfony-style)
class SomeService
{
    public function __construct(
        private LoggerInterface $logger
    ) {}
}
```

## Production Best Practices

### 1. Log Levels

```php
// ❌ Don't log everything as info
Log::info('User clicked button');
Log::info('Query took 150ms');
Log::info('Cache miss');

// ✅ Use appropriate levels
Log::debug('Query took 150ms');
Log::notice('Cache miss');
Log::warning('Slow query: 5000ms');
Log::error('Payment gateway timeout');
```

### 2. Context Over Interpolation

```php
// ❌ String interpolation
Log::info("User {$user->id} performed {$action}");

// ✅ Context with placeholders
Log::info('User {user_id} performed {action}', [
    'user_id' => $user->id,
    'action' => $action
]);
```

### 3. Sensitive Data

```php
// ❌ Don't log sensitive data
Log::info('User login', [
    'username' => 'john',
    'password' => 'secret123'  // ❌
]);

// ✅ Sanitize or exclude
Log::info('User login', [
    'username' => 'john',
    'ip' => $request->ip()
]);
```

### 4. Exception Logging

```php
// ❌ Just the message
try {
    $payment->process();
} catch (\Exception $e) {
    Log::error($e->getMessage());
}

// ✅ Full context
try {
    $payment->process();
} catch (\Exception $e) {
    Log::error('Payment processing failed', [
        'exception' => get_class($e),
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
        'order_id' => $order->id,
        'amount' => $order->amount
    ]);
}
```

### 5. Log Retention

```php
// ❌ Keep logs forever (disk space issues)
$rotator = new DailyRotator(maxDays: 9999);

// ✅ Reasonable retention
$rotator = new DailyRotator(maxDays: 30);    // 30 days for production
$rotator = new DailyRotator(maxDays: 7);     // 7 days for development
```

## Testing Strategy

All logging components have **100% code coverage**:

```
✅ Logger: Full PSR-3 compliance tested
✅ Handlers: File & Database tested
✅ Formatters: Text, JSON, XML tested
✅ Rotators: Daily rotation & cleanup tested
✅ Message: Immutability tested
✅ Context: Structured data tested
✅ Metadata: Auto-generation tested
✅ PlaceholderProcessor: Interpolation tested
```

### Testing Techniques

**1. Clock Freezing**
```php
ClockFactory::freeze('2024-01-15 10:30:45');
// All timestamps are now consistent
```

**2. File System Tests**
```php
$tempDir = sys_get_temp_dir() . '/test_logs_' . uniqid();
mkdir($tempDir);
// Test with real files, clean up after
```

**3. Mock Handlers**
```php
$mockHandler = $this->createMock(HandlerContract::class);
$mockHandler->expects($this->once())
    ->method('handle')
    ->with($this->callback(fn($msg) => $msg->level === LogLevel::ERROR));
```

## Common Use Cases

### Application Logging

```php
// Application lifecycle
Log::info('Application started');
Log::info('Configuration loaded');
Log::info('Database connected');

// Request handling
Log::debug('Request received', [
    'method' => $request->method,
    'path' => $request->path,
    'ip' => $request->ip
]);
```

### Error Tracking

```php
// Catch all exceptions
try {
    $app->handle($request);
} catch (\Throwable $e) {
    Log::critical('Uncaught exception', [
        'exception' => get_class($e),
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
```

### Performance Monitoring

```php
$start = microtime(true);
$result = $heavyOperation->execute();
$duration = microtime(true) - $start;

if ($duration > 1.0) {
    Log::warning('Slow operation detected', [
        'operation' => 'heavy_computation',
        'duration_ms' => $duration * 1000
    ]);
}
```

### Security Logging

```php
// Failed login attempts
Log::warning('Failed login attempt', [
    'username' => $username,
    'ip' => $request->ip(),
    'user_agent' => $request->userAgent()
]);

// Suspicious activity
Log::alert('SQL injection attempt detected', [
    'query' => $suspiciousInput,
    'ip' => $request->ip()
]);
```

## Key Takeaways

1. **PSR-3 Native** - Built-in PSR-3 compliance, not via Monolog
2. **Structured Logging** - Context and metadata for every log
3. **Flexible Handlers** - File, Database, or custom
4. **Multiple Formatters** - Text, JSON, XML
5. **Automatic Rotation** - Daily rotation with cleanup
6. **Facade Pattern** - Convenient static access via `Log`
7. **Type Safety** - Full type hints everywhere
8. **Immutable Messages** - Thread-safe message objects
9. **Placeholder Interpolation** - PSR-3 compliant `{placeholder}` syntax
10. **Production-Ready** - Battle-tested with 940+ passing tests
11. **Zero Dependencies** - No Monolog, built from scratch
12. **Testable** - ClockFactory for time, easy to mock

## Next Chapter

In **Chapter 15**, I'll implement a **Middleware System (PSR-15)** to provide a powerful request/response pipeline for HTTP request processing, authentication, logging, and more.

---

**Note:** This is a production-ready implementation, not tutorial code. Every component is fully tested, type-safe, and follows PSR-3 standards.
