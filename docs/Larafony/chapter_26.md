# Chapter 26: Event System (PSR-14)

> Part of the Larafony Framework - A modern PHP 8.5 framework built from scratch
>
> 📚 Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)

## Overview

Chapter 26 introduces a powerful event-driven architecture based on **PSR-14 (Event Dispatcher)**, enabling loosely coupled application components through publish-subscribe patterns. This implementation provides attribute-based listener registration, automatic event type inference, priority-based execution, and stoppable event propagation — all while maintaining strict PSR compliance.

The event system serves as the foundation for framework-wide observability, powering features like database query logging, cache monitoring, view rendering hooks, and route matching events. It eliminates tight coupling between components by allowing any part of the application to react to events without direct dependencies.

Key features include automatic listener discovery using PHP 8.5 attributes (#[Listen]), priority-based listener execution (higher priority = earlier execution), container-based listener resolution for dependency injection, and framework events for application lifecycle (booting/booted), database operations (queries, transactions), cache operations (hit/miss/write/forget), view rendering (before/after), and route matching.

## Key Components

### Event Dispatcher

- **EventDispatcher** - PSR-14 compliant event dispatcher with support for stoppable events (implements StoppableEventInterface), listener priority ordering (higher executes first), and sequential execution with early termination for stopped events
- **ListenerProvider** - PSR-14 listener provider managing event-to-listener mappings with priority-based sorting (krsort for descending order), container-based listener resolution (automatic DI), and support for both callable and array-based listeners ([ClassName, 'method'])
- **ListenerDiscovery** - Automatic listener registration via reflection scanning public methods for #[Listen] attributes, event type inference from method parameter types, and support for both class names and object instances

### Attribute-Based Registration

- **#[Listen]** - Method attribute for marking event listeners with optional event class specification (auto-inferred from parameter type if not provided), configurable priority (default 0, higher = earlier execution), and repeatable attribute (single method can listen to multiple events)

### Framework Events

The framework dispatches events at critical points in the application lifecycle:

**Application Events:**
- **ApplicationBooting** - Dispatched before service providers boot
- **ApplicationBooted** - Dispatched after all service providers have booted

**Database Events:**
- **QueryExecuted** - Dispatched after each SQL query with query details (SQL, bindings, execution time, connection name) and backtrace for debugging
- **TransactionBeginning** - Dispatched when database transaction starts
- **TransactionCommitted** - Dispatched after successful transaction commit
- **TransactionRolledBack** - Dispatched after transaction rollback

**Cache Events:**
- **CacheHit** - Dispatched when cache key is found (includes key and value)
- **CacheMissed** - Dispatched when cache key is not found (includes key only)
- **KeyWritten** - Dispatched after cache write (includes key, value, TTL)
- **KeyForgotten** - Dispatched after cache deletion (includes key)

**View Events:**
- **ViewRendering** - Dispatched before view rendering (includes view name and data, allows modification)
- **ViewRendered** - Dispatched after view rendering (includes view name, data, and rendered output)

**Routing Events:**
- **RouteMatched** - Dispatched when route is matched to request (includes route name, URI pattern, controller, matched parameters)

### Stoppable Events

- **StoppableEvent** - Abstract base class implementing PSR-14 StoppableEventInterface with `stopPropagation()` method to halt listener execution and `isPropagationStopped()` to check stopped state

## PSR Standards Implemented

- **PSR-14**: Event Dispatcher - Full implementation with EventDispatcher (dispatches events to listeners), ListenerProvider (provides listeners for events), and StoppableEventInterface (allows stopping propagation)
- **PSR-11**: Container Interface - Used for automatic listener instantiation and dependency injection

## New Attributes

### #[Listen]

Marks a method as an event listener with optional event class and priority configuration.

**Parameters:**
- `event` (class-string|null) - Event class name. If null, inferred from first method parameter type
- `priority` (int) - Listener priority. Higher values execute first. Default: 0

**Target:** Methods only (Attribute::TARGET_METHOD)

**Repeatable:** Yes (Attribute::IS_REPEATABLE) - one method can listen to multiple events

**Example:**
```php
use Larafony\Framework\Events\Attributes\Listen;
use Larafony\Framework\Events\Database\QueryExecuted;
use Larafony\Framework\Events\Cache\CacheHit;

class MyListener
{
    // Explicit event class
    #[Listen(event: QueryExecuted::class, priority: 10)]
    public function onQuery(QueryExecuted $event): void
    {
        // High priority (10) - executes before priority 0
    }

    // Auto-inferred from parameter type
    #[Listen]
    public function onCacheHit(CacheHit $event): void
    {
        // Event type inferred from parameter
    }

    // Multiple listeners on same method
    #[Listen(event: CacheHit::class)]
    #[Listen(event: CacheMissed::class)]
    public function onCacheAccess(object $event): void
    {
        // Handles both CacheHit and CacheMissed
    }
}
```

## Usage Examples

### Basic Event Listening

```php
<?php

use Larafony\Framework\Events\EventDispatcher;
use Larafony\Framework\Events\ListenerProvider;
use Larafony\Framework\Events\Database\QueryExecuted;

// Manual listener registration
$provider = new ListenerProvider();
$dispatcher = new EventDispatcher($provider);

// Register listener with priority
$provider->listen(
    QueryExecuted::class,
    function (QueryExecuted $event) {
        echo "Query: {$event->sql}\n";
        echo "Time: {$event->time}ms\n";
    },
    priority: 5
);

// Dispatch event
$event = new QueryExecuted(
    sql: 'SELECT * FROM users WHERE id = ?',
    rawSql: 'SELECT * FROM users WHERE id = 1',
    bindings: [1],
    time: 2.45,
    connection: 'mysql'
);

$dispatcher->dispatch($event);
```

### Attribute-Based Listeners

```php
<?php

use Larafony\Framework\Events\Attributes\Listen;
use Larafony\Framework\Events\Database\QueryExecuted;
use Larafony\Framework\Events\Cache\CacheHit;
use Larafony\Framework\Events\Cache\CacheMissed;

class ApplicationMonitor
{
    // High priority query logging
    #[Listen(priority: 100)]
    public function logSlowQueries(QueryExecuted $event): void
    {
        if ($event->time > 100) {
            // Log slow queries (>100ms)
            error_log("SLOW QUERY: {$event->sql} ({$event->time}ms)");
        }
    }

    // Cache monitoring
    #[Listen]
    public function trackCacheHitRate(CacheHit $event): void
    {
        // Increment cache hit counter
        $this->incrementMetric('cache.hits');
    }

    #[Listen]
    public function trackCacheMissRate(CacheMissed $event): void
    {
        // Increment cache miss counter
        $this->incrementMetric('cache.misses');
    }

    // Multiple events, one handler
    #[Listen(event: CacheHit::class)]
    #[Listen(event: CacheMissed::class)]
    public function logCacheAccess(object $event): void
    {
        $type = $event instanceof CacheHit ? 'HIT' : 'MISS';
        echo "Cache {$type}: {$event->key}\n";
    }

    private function incrementMetric(string $name): void
    {
        // Implementation...
    }
}
```

### Automatic Listener Discovery

```php
<?php

use Larafony\Framework\Events\ListenerProvider;
use Larafony\Framework\Events\ListenerDiscovery;
use Larafony\Framework\Events\EventDispatcher;

// Create provider and dispatcher
$provider = new ListenerProvider();
$dispatcher = new EventDispatcher($provider);

// Register listener classes for discovery
$discovery = new ListenerDiscovery(
    provider: $provider,
    listenerClasses: [
        ApplicationMonitor::class,
        QueryLogger::class,
        CacheMonitor::class,
    ]
);

// Discover and register all #[Listen] methods
$discovery->discover();

// Now all listeners are registered and ready
// Events will be automatically routed to appropriate listeners
```

### Stoppable Events

```php
<?php

use Larafony\Framework\Events\StoppableEvent;
use Larafony\Framework\Events\Attributes\Listen;

// Custom stoppable event
class UserRegistering extends StoppableEvent
{
    public function __construct(
        public string $email,
        public string $password,
        public ?string $reason = null
    ) {
    }
}

class RegistrationValidator
{
    #[Listen(priority: 100)]
    public function validateEmail(UserRegistering $event): void
    {
        if (!filter_var($event->email, FILTER_VALIDATE_EMAIL)) {
            $event->reason = 'Invalid email format';
            $event->stopPropagation(); // Stop further listeners
        }
    }

    #[Listen(priority: 50)]
    public function checkBlacklist(UserRegistering $event): void
    {
        if ($this->isBlacklisted($event->email)) {
            $event->reason = 'Email is blacklisted';
            $event->stopPropagation();
        }
    }

    #[Listen(priority: 0)]
    public function createUser(UserRegistering $event): void
    {
        // Only executes if not stopped
        echo "Creating user: {$event->email}\n";
    }

    private function isBlacklisted(string $email): bool
    {
        // Check blacklist...
        return false;
    }
}

// Usage
$event = new UserRegistering('spam@example.com', 'password');
$dispatcher->dispatch($event);

if ($event->isPropagationStopped()) {
    echo "Registration failed: {$event->reason}\n";
}
```

### Container-Based Listener Resolution

```php
<?php

use Larafony\Framework\DI\Container;
use Larafony\Framework\Events\ListenerProvider;
use Larafony\Framework\Events\EventDispatcher;
use Larafony\Framework\Events\Database\QueryExecuted;
use Larafony\Framework\Log\Log;

class DatabaseLogger
{
    public function __construct(
        private readonly Log $logger // Injected from container
    ) {
    }

    #[Listen]
    public function logQuery(QueryExecuted $event): void
    {
        $this->logger->debug('Query executed', [
            'sql' => $event->sql,
            'time' => $event->time,
        ]);
    }
}

// Setup container
$container = new Container();
$container->singleton(Log::class, fn() => Log::instance());

// Provider with container
$provider = new ListenerProvider($container);

// Register listener class (not instance)
$provider->listen(
    QueryExecuted::class,
    [DatabaseLogger::class, 'logQuery']
);

// When event is dispatched, DatabaseLogger will be resolved from container
// and Log dependency will be automatically injected
$dispatcher = new EventDispatcher($provider);
$dispatcher->dispatch(new QueryExecuted(/* ... */));
```

### Framework Integration

```php
<?php

// In your application bootstrap (e.g., web/bootstrap.php or console/bootstrap.php)

use Larafony\Framework\Web\Application;
use Larafony\Framework\Events\ServiceProviders\EventServiceProvider;

$app = Application::instance();

// EventServiceProvider is automatically registered in config/app.php
// It sets up EventDispatcher, ListenerProvider, and ListenerDiscovery

// Register your listeners in config/events.php
// config/events.php
return [
    'listeners' => [
        \App\Listeners\ApplicationMonitor::class,
        \App\Listeners\QueryLogger::class,
        \App\Listeners\CacheMonitor::class,
    ],
];

// Framework will automatically:
// 1. Register all listeners from config
// 2. Discover #[Listen] attributes
// 3. Register listeners with provider
// 4. Make EventDispatcher available via DI
```

### Real-World Example: Query Performance Monitor

```php
<?php

namespace App\Listeners;

use Larafony\Framework\Events\Attributes\Listen;
use Larafony\Framework\Events\Database\QueryExecuted;
use Larafony\Framework\Cache\Cache;

class QueryPerformanceMonitor
{
    private const SLOW_QUERY_THRESHOLD = 100; // ms
    private const VERY_SLOW_QUERY_THRESHOLD = 500; // ms

    #[Listen(priority: 10)]
    public function monitorQueryPerformance(QueryExecuted $event): void
    {
        // Track slow queries
        if ($event->time > self::VERY_SLOW_QUERY_THRESHOLD) {
            $this->alertVerySlowQuery($event);
        } elseif ($event->time > self::SLOW_QUERY_THRESHOLD) {
            $this->logSlowQuery($event);
        }

        // Track query statistics
        $this->updateStatistics($event);
    }

    private function alertVerySlowQuery(QueryExecuted $event): void
    {
        error_log(sprintf(
            "[CRITICAL] Very slow query (%sms): %s\nBacktrace: %s",
            $event->time,
            $event->rawSql,
            json_encode($event->backtrace, JSON_PRETTY_PRINT)
        ));

        // Could also send to monitoring service (Sentry, etc.)
    }

    private function logSlowQuery(QueryExecuted $event): void
    {
        error_log(sprintf(
            "[WARNING] Slow query (%sms): %s",
            $event->time,
            $event->rawSql
        ));
    }

    private function updateStatistics(QueryExecuted $event): void
    {
        $cache = Cache::instance();

        // Increment total query count
        $cache->increment('stats.queries.total');

        // Track by connection
        $cache->increment("stats.queries.{$event->connection}");

        // Track total execution time
        $totalTime = $cache->get('stats.queries.time', 0);
        $cache->put('stats.queries.time', $totalTime + $event->time, 3600);

        // Track slow query count
        if ($event->time > self::SLOW_QUERY_THRESHOLD) {
            $cache->increment('stats.queries.slow');
        }
    }
}
```

## Implementation Details

### EventDispatcher

**Location:** `src/Larafony/Events/EventDispatcher.php`

**Purpose:** Core PSR-14 event dispatcher responsible for delivering events to registered listeners.

**Key Methods:**
- `dispatch(object $event): object` - Dispatch event to all registered listeners, respecting priority order and stoppable events (src/Larafony/Events/EventDispatcher.php:18)

**Algorithm:**
1. Retrieve listeners for event from ListenerProvider
2. Sort by priority (handled by provider)
3. For each listener:
   - Check if event is stoppable and stopped
   - If stopped, break loop
   - Otherwise, invoke listener with event
4. Return modified event object

**Dependencies:** Requires ListenerProviderInterface for listener resolution

**Example:**
```php
$dispatcher = new EventDispatcher($provider);

$event = new QueryExecuted(/* ... */);
$result = $dispatcher->dispatch($event);

// $result is the same event object, potentially modified by listeners
```

### ListenerProvider

**Location:** `src/Larafony/Events/ListenerProvider.php`

**Purpose:** PSR-14 listener provider managing event-to-listener mappings with priority support and container-based resolution.

**Key Methods:**
- `listen(string $eventClass, callable|array $listener, int $priority = 0): void` - Register listener for event class with priority (src/Larafony/Events/ListenerProvider.php:27)
- `getListenersForEvent(object $event): iterable<callable>` - Get all listeners for event, sorted by priority descending (src/Larafony/Events/ListenerProvider.php:43)
- `resolveListener(callable|array $listener): callable` - Resolve listener from class name or instance, using container if available (src/Larafony/Events/ListenerProvider.php:67)

**Data Structure:**
```php
[
    EventClass::class => [
        100 => [listener1, listener2],  // High priority
        50 => [listener3],
        0 => [listener4, listener5],     // Default priority
        -10 => [listener6],              // Low priority
    ]
]
```

**Priority Sorting:** Uses `krsort()` to sort priorities in descending order (100, 50, 0, -10)

**Container Resolution:**
- If listener is `[ClassName::class, 'method']` and container has ClassName, resolve from container
- Otherwise, create new instance
- If listener is `[$instance, 'method']`, use instance directly
- If listener is closure, use as-is

**Example:**
```php
$provider = new ListenerProvider($container);

// High priority
$provider->listen(QueryExecuted::class, $listener1, 100);

// Default priority
$provider->listen(QueryExecuted::class, $listener2);

// Low priority
$provider->listen(QueryExecuted::class, $listener3, -10);

// Execution order: listener1 (100) -> listener2 (0) -> listener3 (-10)
```

### ListenerDiscovery

**Location:** `src/Larafony/Events/ListenerDiscovery.php`

**Purpose:** Automatic listener registration by scanning classes for #[Listen] attributes using reflection.

**Key Methods:**
- `discover(): void` - Scan all registered listener classes and register methods with #[Listen] attribute (src/Larafony/Events/ListenerDiscovery.php:22)
- `registerListenersFromClass(string $className): void` - Register listeners from class name (lazy instantiation) (src/Larafony/Events/ListenerDiscovery.php:73)
- `registerListenersFromInstance(object $instance): void` - Register listeners from object instance (src/Larafony/Events/ListenerDiscovery.php:36)
- `inferEventClass(ReflectionMethod $method): ?string` - Infer event class from first parameter type (src/Larafony/Events/ListenerDiscovery.php:109)

**Discovery Algorithm:**
1. For each listener class/instance:
   2. Get reflection
   3. Scan all public methods
   4. For each method with #[Listen] attribute:
      5. Get Listen attribute instance
      6. Use explicit event class OR infer from first parameter type
      7. Throw exception if event class cannot be determined
      8. Register with provider using [class, method] or [instance, method]

**Event Type Inference:**
- Checks first method parameter
- Must be a named type (not union/intersection)
- Must not be built-in type
- Returns class-string

**Example:**
```php
$discovery = new ListenerDiscovery(
    provider: $provider,
    listenerClasses: [
        QueryLogger::class,        // Class name
        new CacheMonitor(),        // Instance
    ]
);

$discovery->discover();
// All #[Listen] methods now registered
```

### StoppableEvent

**Location:** `src/Larafony/Events/StoppableEvent.php`

**Purpose:** Abstract base class for events that can stop propagation to remaining listeners.

**Key Methods:**
- `stopPropagation(): void` - Stop event propagation (src/Larafony/Events/StoppableEvent.php:11)
- `isPropagationStopped(): bool` - Check if propagation is stopped (src/Larafony/Events/StoppableEvent.php:16)

**Usage Pattern:**
```php
class UserRegistering extends StoppableEvent
{
    public function __construct(
        public string $email,
        public ?string $errorMessage = null
    ) {
    }
}

// In validator listener:
if (!$this->isValid($event->email)) {
    $event->errorMessage = 'Invalid email';
    $event->stopPropagation();
}
```

### Framework Events

All framework events are simple DTOs (Data Transfer Objects) with public readonly properties.

**QueryExecuted**
```php
new QueryExecuted(
    sql: 'SELECT * FROM users WHERE id = ?',
    rawSql: 'SELECT * FROM users WHERE id = 1',
    bindings: [1],
    time: 2.45,
    connection: 'mysql',
    backtrace: debug_backtrace()
);
```

**CacheHit / CacheMissed / KeyWritten / KeyForgotten**
```php
new CacheHit(key: 'user.123', value: $userData);
new CacheMissed(key: 'user.456');
new KeyWritten(key: 'config.app', value: $config, ttl: 3600);
new KeyForgotten(key: 'session.abc');
```

**ViewRendering / ViewRendered**
```php
new ViewRendering(name: 'welcome', data: ['user' => $user]);
new ViewRendered(name: 'welcome', data: ['user' => $user], output: '<html>...</html>');
```

**RouteMatched**
```php
new RouteMatched(
    name: 'users.show',
    uri: '/users/{id}',
    controller: UserController::class,
    action: 'show',
    parameters: ['id' => '123']
);
```

## Comparison with Other Frameworks

| Feature | Larafony | Laravel | Symfony |
|---------|----------|---------|---------|
| **PSR-14 Compliance** | Full PSR-14 implementation | Custom event system, not PSR-14 | Full PSR-14 with EventDispatcher component |
| **Listener Registration** | Attribute-based (#[Listen]) + manual | Array config + event discovery | Array config + tag-based DI + subscribers |
| **Priority Support** | Built-in priority parameter | Built-in priority parameter | Built-in priority parameter |
| **Event Type Inference** | Automatic from parameter type | No automatic inference | No automatic inference |
| **Stoppable Events** | PSR-14 StoppableEventInterface | Custom stopped() method | PSR-14 StoppableEventInterface |
| **Container Integration** | Optional PSR-11 container | Laravel Container required | Symfony DI Container required |
| **Listener Discovery** | Attribute scanning via reflection | Service provider registration | Tag-based DI + subscribers |
| **Framework Events** | Database, Cache, View, Routing, Application | Extensive (100+ events) | Component-specific events |
| **Closure Listeners** | Supported | Supported | Supported |
| **Event Subscribers** | Not implemented (use #[Listen] repeatable) | Supported | Supported |
| **Queued Listeners** | Not implemented | Supported (ShouldQueue interface) | Requires Messenger component |
| **Event Middleware** | Not implemented | Not available | Not available |

**Key Differences:**

- **PSR-First**: Larafony strictly follows PSR-14, making events interoperable with any PSR-14 compatible library. Laravel uses a custom event system with different method signatures.
- **Attribute-Based Discovery**: Larafony uses PHP 8.5 #[Listen] attributes for automatic listener discovery. Laravel requires manual registration in EventServiceProvider. Symfony uses DI tags or subscriber classes.
- **Type Inference**: Larafony automatically infers event type from method parameter, reducing boilerplate. Laravel and Symfony require explicit mapping.
- **Minimal Dependencies**: Larafony event system has zero required dependencies (PSR-11 container is optional). Laravel requires full framework. Symfony EventDispatcher is standalone but typically used with DI.
- **Simplicity**: Larafony focuses on core event dispatching without queued listeners, event subscribers, or complex middleware patterns. This keeps the implementation simple and performant.
- **Framework Events**: Larafony provides essential framework events (queries, cache, views, routes). Laravel has 100+ events covering every framework aspect. Symfony events are component-specific.

## Testing

The event system is covered by comprehensive test suites:

### EventDispatcherTest

**Location:** `tests/Larafony/Events/EventDispatcherTest.php`

**Coverage:** 6 tests covering EventDispatcher functionality
- Basic event dispatching
- Multiple listeners on same event
- Priority-based execution order
- Stoppable event propagation
- Event modification by listeners
- Empty listener handling

**All tests pass:** ✅ 6/6 tests

### ListenerProviderTest

**Location:** `tests/Larafony/Events/ListenerProviderTest.php`

**Coverage:** 8 tests covering ListenerProvider functionality
- Manual listener registration
- Priority-based sorting
- Container-based resolution
- Class name vs instance listeners
- Multiple priorities
- No listeners for event
- Callable resolution

**All tests pass:** ✅ 8/8 tests

### ListenerDiscoveryTest

**Location:** `tests/Larafony/Events/ListenerDiscoveryTest.php`

**Coverage:** 7 tests covering ListenerDiscovery functionality
- Attribute scanning and registration
- Event type inference
- Explicit event class specification
- Priority configuration
- Repeatable #[Listen] attributes
- Class vs instance discovery
- Error handling for missing event types

**All tests pass:** ✅ 7/7 tests

**Total Test Coverage:**
- 21 tests across 3 test suites
- 40+ assertions
- 100% pass rate
- Covers all PSR-14 functionality

---

📚 **Learn More:** This implementation is explained in detail with step-by-step tutorials, tests, and best practices at [masterphp.eu](https://masterphp.eu)
