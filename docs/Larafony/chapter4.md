# Chapter 4: Dependency Injection Container - PSR-11

This chapter covers the implementation of a powerful, autowiring-enabled Dependency Injection Container compatible with PSR-11.

## Overview

Larafony's Container provides:
- **PSR-11 compatible** - Implements `Psr\Container\ContainerInterface`
- **Automatic dependency resolution** - Autowiring with reflection
- **Service Providers** - Laravel-style service provider pattern
- **Dot notation support** - Access nested configuration with `config.app.name`
- **Type-safe bindings** - Full PHP 8.5 type system support
- **Zero configuration** - Works out of the box for most use cases

## Architecture

The Container system consists of:

### 1. Container (`src/Larafony/Container/Container.php`)

Main container implementing PSR-11:

```php
interface ContainerContract extends ContainerInterface
{
    public function set(string $key, mixed $value): self;
    public function get(string $id): mixed;
    public function has(string $id): bool;
    public function bind(string $key, float|bool|int|string|null $value): void;
    public function getBinding(string $key): string|int|float|bool|null;
}
```

### 2. Autowire (`src/Larafony/Container/Resolvers/Autowire.php`)

Automatic dependency resolution using reflection:
- Constructor injection
- Recursive dependency resolution
- Default value support
- Built-in type handling

### 3. ServiceProvider (`src/Larafony/Container/ServiceProvider.php`)

Abstract base for registering services:

```php
interface ServiceProviderContract
{
    /**
     * @var array<int|string, class-string>
     */
    public array $providers { get; }
    public function register(ContainerContract $container): self;
    public function boot(ContainerContract $container): void;
}
abstract class ServiceProvider implements ServiceProviderContract
{
    public function register(ContainerContract $container): self;
    public function boot(ContainerContract $container): void;
}
```

### 4. DotContainer (`src/Larafony/Container/Helpers/DotContainer.php`)

Supports dot notation for nested array access:
- `config.app.name`
- `database.connections.mysql.host`

## Basic Usage

### Simple Binding and Retrieval

```php
use Larafony\Framework\Container\Container;

$container = new Container();

// Set a value
$container->bind('database.host', 'localhost');
$container->bind('database.port', 3306);

// Retrieve a value
$host = $container->bind('database.host'); // 'localhost'
```

### Autowiring Simple Classes

```php
class Database
{
    public function connect(): void
    {
        echo "Connected to database\n";
    }
}

$container = new Container();

// Automatically resolves and creates instance
$db = $container->get(Database::class);
$db->connect();
```

### Autowiring with Dependencies

```php
class Database
{
    public function __construct(
        private string $host = 'localhost',
        private int $port = 3306,
    ) {}
}

class UserRepository
{
    public function __construct(
        private readonly Database $database,
    ) {}
}

$container = new Container();

// Container automatically resolves Database dependency
$repo = $container->get(UserRepository::class);
```

### Binding by Class Name

```php
// Bind a class-string - will be autowired when retrieved
$container->set(Database::class, Database::class);

// Get autowired instance
$db = $container->get(Database::class);
```

### Binding Interfaces to Implementations

```php
interface LoggerInterface {}

class FileLogger implements LoggerInterface
{
    public function log(string $message): void
    {
        file_put_contents('app.log', $message . PHP_EOL, FILE_APPEND);
    }
}

$container = new Container();

// Bind interface to implementation
$container->set(LoggerInterface::class, FileLogger::class);

// Resolves to FileLogger instance
$logger = $container->get(LoggerInterface::class);
$logger->log('Application started');
```


## Service Providers

Service Providers organize registration and bootstrapping logic.

### Creating a Service Provider

```php
use Larafony\Framework\Container\ServiceProvider;
use Larafony\Framework\Container\Contracts\ContainerContract;

class DatabaseServiceProvider extends ServiceProvider
{
    // Define services this provider offers
    public array $providers {
        get => [
            Database::class,
            'db.connection' => Database::class,
        ];
    }

    public function register(ContainerContract $container): self
    {
        // Register services
        parent::register($container);

        // Additional registration logic
        $container->bind('db.prefix', 'app_');

        return $this;
    }

    public function boot(ContainerContract $container): void
    {
        // Bootstrap logic (runs after all providers registered)
        $db = $container->get(Database::class);
        $db->connect();
    }
}
```

### Using Service Providers

```php
$container = new Container();

// Register and boot provider
$provider = new DatabaseServiceProvider();
$provider->register($container)->boot($container);

// Use registered services
$db = $container->get(Database::class);
$connection = $container->get('db.connection');
```

### Real-World Example: Error Handler Provider

```php
use Larafony\Framework\Container\ServiceProvider;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\ErrorHandler\DetailedErrorHandler;

class ErrorHandlerServiceProvider extends ServiceProvider
{
    public array $provides {
        get => [DetailedErrorHandler::class];
    }

    public function register(ContainerContract $container): self
    {
        parent::register($container);
        return $this;
    }

    public function boot(ContainerContract $container): void
    {
        $handler = $container->get(DetailedErrorHandler::class);
        $handler->register();
    }
}

// In your application bootstrap
$container = new Container();
new ErrorHandlerServiceProvider()->register($container)->boot($container);
```
### Dependency Resolution Order

The autowire resolver follows this priority:

1. **Exact parameter name match** - `$container->has($parameterName)`
2. **Type hint match** - `$container->has($type)`
3. **Default value** - From constructor parameter
4. **Nullable** - Returns `null` if allowed
5. **Built-in type defaults** - `0`, `''`, `false`, `[]` for built-ins
6. **Recursive resolution** - Autowire class dependencies
7. **Exception** - Throws `NotFoundError` if unresolvable

Example:

```php
class Service
{
    public function __construct(
        private Database $database,      // Resolved by type
        private string $apiKey,          // Resolved by name from container
        private int $timeout = 30,       // Uses default value
        private ?Logger $logger = null,  // Nullable, defaults to null
    ) {}
}

$container = new Container();
$container->set('apiKey', 'my-secret-key');

$service = $container->get(Service::class);
// Database: autowired
// apiKey: 'my-secret-key' from container
// timeout: 30 (default)
// logger: null (nullable)
```

### Complex Dependency Graph

```php
class Database {}

class Cache
{
    public function __construct(private Database $db) {}
}

class Logger
{
    public function __construct(private Database $db) {}
}

class UserService
{
    public function __construct(
        private Database $database,
        private Cache $cache,
        private Logger $logger,
    ) {}
}

$container = new Container();

// Automatically resolves entire dependency tree
$userService = $container->get(UserService::class);
// Creates: Database, Cache (with Database), Logger (with Database), UserService
```

## Testing

### Mocking Dependencies

```php
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    public function testUserCreation(): void
    {
        $container = new Container();

        // Mock database
        $mockDb = $this->createMock(Database::class);
        $mockDb->method('insert')->willReturn(true);

        // Inject mock
        $container->set(Database::class, $mockDb);

        $service = $container->get(UserService::class);
        $result = $service->createUser(['name' => 'John']);

        $this->assertTrue($result);
    }
}
```

### Testing Service Providers

```php
class ServiceProviderTest extends TestCase
{
    public function testProviderRegistersServices(): void
    {
        $container = new Container();
        $provider = new DatabaseServiceProvider();

        $provider->register($container);

        $this->assertTrue($container->has(Database::class));
        $this->assertTrue($container->has('db.connection'));
    }

    public function testProviderBootstrapsCorrectly(): void
    {
        $container = new Container();
        $provider = new DatabaseServiceProvider();

        $provider->register($container)->boot($container);

        $db = $container->get(Database::class);
        $this->assertInstanceOf(Database::class, $db);
    }
}
```
### Lazy Loading

The container resolves dependencies only when requested:

```php
// This doesn't instantiate anything
$container->set(Database::class, Database::class);

// Only instantiated when retrieved
$db = $container->get(Database::class);
```

## Best Practices

1. **Use Service Providers for module organization**
   ```php
   // Good: Group related services
   class AuthServiceProvider extends ServiceProvider
   {
       public array $provides {
           get => [
               AuthManager::class,
               SessionHandler::class,
               TokenValidator::class,
           ];
       }
   }
   ```

2. **Bind interfaces, not implementations**
   ```php
   // Good
   $container->set(CacheInterface::class, RedisCache::class);

   // Avoid
   $container->set(RedisCache::class, RedisCache::class);
   ```

## Error Handling

### NotFoundError Exception

Thrown when a dependency cannot be resolved:

```php
use Larafony\Framework\Container\Exceptions\NotFoundError;

try {
    $service = $container->get(NonExistentClass::class);
} catch (NotFoundError $e) {
    echo "Cannot resolve: " . $e->getMessage();
}
```

## Testing

Run the Container tests:

```bash
cd framework
composer test -- tests/Larafony/Container/
```

All Container tests: **17 tests, 31 assertions**

### Key Differences from Other Containers

| Feature                    | Laravel Container | Symfony DI                           | **Larafony Container**     |
|---------------------------|-------------------|--------------------------------------|----------------------------|
| Autowiring                | ✓                 | ✓                                    | **✓**                      |
| PSR-11                    | ✓                 | ✓                                    | **✓**                      |
| Service Providers         | ✓                 | ✗                                    | **✓**                      |
| Dot Notation              | ✗                 | ✗                                    | **✓**                      |
| Zero Config (by default)  | ✓                 | config-first (autowiring available)  | **✓**                      |
| Reflection-based Runtime  | ✓                 | ✗ (compiled container)               | **✓**                      |
| Property Hooks (PHP 8.5)  | ✗                 | ✗                                    | **✓**                      |
| Tagged Services           | ✓                 | ✓                                    | **✓**                      |
| Contextual Binding        | ✓                 | ✓                                    | **✓**                      |
| Deferred/Lazy Services    | ~ (patterns)      | ✓ (proxies)                          | **✓ (opt-in / planned)**   |
| Freeze After Boot         | ✗                 | ✓ (compiled)                         | **✓**                      |
| Boot Priority             | registration order| n/a (no providers)                   | **✓ registration order**  |

#### Notes

- **Autowiring** — Resolving constructor-type hinted dependencies automatically. All three support it; Symfony typically encourages explicit configuration but autowiring is available.
- **PSR-11** — All expose (or can expose) a PSR-11 compatible API for `get()` / `has()`.
- **Service Providers** — First-class concept in Laravel and Larafony for grouping bindings and boot logic. Symfony relies on bundles/compilers rather than “providers”.
- **Dot Notation** — Larafony lets you reference nested IDs like `db.connection.mysql` for readability and grouping.
- **Zero Config** — “Works out of the box” for common cases without YAML/XML/PHP config. Symfony is config-first by default, but supports zero-config via autowiring.
- **Reflection-based Runtime vs Compiled** — Laravel/Larafony resolve at runtime using reflection (fast to iterate). Symfony generates a compiled container for maximum runtime performance.
- **Property Hooks (PHP 8.5)** — Larafony can lazily resolve dependencies when a property is first accessed (clean ergonomics for framework internals). Requires PHP 8.4+.
- **Tagged Services** — Useful for registries/pipelines (e.g., HTTP middleware). Supported in all three; Larafony provides simple `tag()` / `tagged()` helpers.
- **Contextual Binding** — Choose different implementations per consumer (e.g., `ClockInterface` for a specific service). Supported across the board; Larafony offers a `when()->needs()->give()` style API.
- **Deferred/Lazy Services** — Symfony supports lazy services via generated proxies. Laravel no longer has “deferred providers” but lazy patterns are possible. Larafony offers opt-in deferred providers/lazy closures (planned).
- **Freeze After Boot** — Larafony can lock the container after boot to prevent accidental mutations. Symfony’s compiled container is effectively read-only; Laravel keeps the container mutable.
- **Boot Priority** — Laravel and Larafony rely on registration order; Symfony doesn’t use providers.


## Related Documentation

- [Framework README](../../README.md)
- [Chapter 1: Project Setup](./chapter1.md)
- [Chapter 2: Error Handling](./chapter2.md)
- [Chapter 3: Clock](./chapter3.md)

## References

- [PSR-11: Container Interface](https://www.php-fig.org/psr/psr-11/)
- [Dependency Injection Pattern](https://en.wikipedia.org/wiki/Dependency_injection)
- [Service Locator Pattern](https://en.wikipedia.org/wiki/Service_locator_pattern)
