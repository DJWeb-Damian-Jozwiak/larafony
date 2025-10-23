# Chapter 4: Dependency Injection Container

> Part of the Larafony Framework - A modern PHP 8.5 framework built from scratch
>
> 📚 Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)

## Overview

Chapter 4 introduces a powerful dependency injection container to the Larafony framework, implementing the PSR-11 Container Interface standard with advanced autowiring capabilities. This component provides automatic dependency resolution through reflection, service provider pattern for organizing application services, and a flexible dot notation container for nested configuration management.

The implementation focuses on developer experience with zero-configuration autowiring that automatically resolves constructor dependencies recursively. The container intelligently handles complex dependency graphs by checking for registered bindings, falling back to default values, respecting nullable types, and automatically instantiating class dependencies. This eliminates boilerplate code while maintaining full control through explicit bindings when needed.

The architecture introduces **ServiceProvider** as the standard way to organize and register application services, **Autowire** for automatic dependency injection using reflection, **ReflectionResolver** for analyzing class constructors and parameter types, and **DotContainer** for managing nested configuration with array-like dot notation access. All components use PHP 8.5's modern features including readonly properties, constructor property promotion, match expressions, and the new `#[NoDiscard]` attribute for critical return values.

## Key Components

### Container Core

- **Container** - PSR-11 compliant dependency injection container with autowiring and dot notation support (implements ContainerContract which extends PSR-11 ContainerInterface)
- **ContainerContract** - Extended PSR-11 interface adding bind(), getBinding(), and set() methods for Larafony-specific functionality

### Service Organization

- **ServiceProvider** - Abstract base class for organizing service registrations with providers() array and register()/boot() lifecycle methods
- **ServiceProviderContract** - Interface defining the service provider contract with register() method
- **ErrorHandlerServiceProvider** - Concrete implementation demonstrating service provider pattern for error handler registration and bootstrapping

### Autowiring System

- **Autowire** - Automatic dependency resolution engine using ReflectionResolver to analyze and instantiate classes with their dependencies
- **ReflectionResolver** - Reflection-based parameter analyzer providing constructor introspection, type detection, default value handling, and null checking (helper classes: uses ReflectionClass, ReflectionParameter, ReflectionNamedType from PHP core)

### Configuration Management

- **DotContainer** - ArrayObject extension with dot notation support for nested array access (helper classes: ArrayGet for retrieving nested values, ArraySet for setting nested values)

### Exceptions

- **ContainerError** - PSR-11 ContainerException for general container errors
- **NotFoundError** - PSR-11 NotFoundExceptionInterface for missing dependencies or bindings

## PSR Standards Implemented

- **PSR-11**: Container Interface - Full implementation of ContainerInterface (get(), has()) with extended ContainerContract for framework-specific needs
- **PSR-4**: Autoloading for `Larafony\Framework\Container\` namespace
- **Type Safety**: Strict typing with `declare(strict_types=1)` throughout all container components
- **Dependency Inversion**: Container depends on contracts (ContainerContract, AutowireContract, ReflectionResolverContract) not concrete implementations

## New Attributes

This chapter doesn't introduce new PHP attributes, but extensively uses PHP 8.5 features:

- `readonly` properties in Container and DotContainer
- Constructor property promotion throughout
- `match` expressions in ReflectionResolver for default values
- `#[NoDiscard]` attribute on Container::getBinding() to prevent ignoring return value
- Union types like `string|int|float|bool|null` in bind() method
- Template generics in Autowire::instantiate() for type-safe object creation

## Usage Examples

### Basic Example - Automatic Dependency Injection

```php
<?php

use Larafony\Framework\Container\Container;

require_once __DIR__ . '/vendor/autoload.php';

// Classes with dependencies
class Database
{
    public function __construct(
        private string $host = 'localhost',
        private int $port = 3306
    ) {}

    public function connect(): string
    {
        return "Connected to {$this->host}:{$this->port}";
    }
}

class UserRepository
{
    public function __construct(
        private Database $database  // Automatically resolved!
    ) {}

    public function find(int $id): string
    {
        return $this->database->connect() . " - Found user {$id}";
    }
}

// Zero configuration autowiring
$container = new Container();

// Container automatically resolves Database dependency
$repository = $container->get(UserRepository::class);
echo $repository->find(1);
// Output: Connected to localhost:3306 - Found user 1
```

### Advanced Example - Explicit Bindings and Interfaces

```php
<?php

use Larafony\Framework\Container\Container;

interface LoggerInterface
{
    public function log(string $message): void;
}

class FileLogger implements LoggerInterface
{
    public function __construct(private string $path) {}

    public function log(string $message): void
    {
        echo "Logging to {$this->path}: {$message}\n";
    }
}

class UserService
{
    public function __construct(
        private LoggerInterface $logger  // Interface type-hint
    ) {}

    public function createUser(string $name): void
    {
        $this->logger->log("Creating user: {$name}");
    }
}

// Container with explicit bindings
$container = new Container();

// Bind interface to implementation
$container->set(LoggerInterface::class, FileLogger::class);

// Bind constructor parameter by name
$container->bind('path', '/var/log/app.log');

// Container resolves the chain: UserService → LoggerInterface → FileLogger
$service = $container->get(UserService::class);
$service->createUser('John Doe');
// Output: Logging to /var/log/app.log: Creating user: John Doe
```

### Service Provider Example

```php
<?php

use Larafony\Framework\Container\Container;
use Larafony\Framework\Container\ServiceProvider;
use Larafony\Framework\Container\Contracts\ContainerContract;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register services this provider manages
     */
    public function providers(): array
    {
        return [
            // Interface binding (key => class)
            LoggerInterface::class => FileLogger::class,

            // Self-binding (just class name)
            UserService::class,
            Database::class,
        ];
    }

    /**
     * Register bindings (called automatically by parent)
     */
    public function register(ContainerContract $container): self
    {
        parent::register($container);

        // Additional configuration bindings
        $container->bind('db.host', 'production.db.example.com');
        $container->bind('db.port', 5432);
        $container->bind('log.path', '/var/log/production.log');

        return $this;
    }

    /**
     * Bootstrap services (called after registration)
     */
    public function boot(ContainerContract $container): void
    {
        // Initialize services, run setup logic, etc.
        $logger = $container->get(LoggerInterface::class);
        $logger->log('Application bootstrapped');
    }
}

// Usage
$container = new Container();
$provider = new AppServiceProvider();

$provider->register($container)->boot($container);

$service = $container->get(UserService::class);
$service->createUser('Production User');
// Output:
// Logging to /var/log/production.log: Application bootstrapped
// Logging to /var/log/production.log: Creating user: Production User
```

### Dot Notation Configuration

```php
<?php

use Larafony\Framework\Container\Helpers\DotContainer;

// Nested configuration management
$config = new DotContainer([
    'database' => [
        'connections' => [
            'mysql' => [
                'host' => 'localhost',
                'port' => 3306,
            ],
            'pgsql' => [
                'host' => 'pg.example.com',
                'port' => 5432,
            ]
        ]
    ]
]);

// Access nested values with dot notation
echo $config->get('database.connections.mysql.host'); // localhost
echo $config->get('database.connections.pgsql.port'); // 5432

// Set nested values
$config->set('database.connections.redis.host', 'redis.example.com');
$config->set('database.connections.redis.port', 6379);

// Check existence
$config->has('database.connections.redis'); // true
$config->has('database.connections.mongodb'); // false

// Default values for missing keys
echo $config->get('missing.key', 'default'); // default
```

## Implementation Details

### Container

**Location:** `src/Larafony/Container/Container.php:16`

**Purpose:** PSR-11 compliant dependency injection container with autowiring and configuration management.

**Key Methods:**
- `bind(string $key, string|int|float|bool|null $value): void` - Bind simple values (scalars) to the container
- `getBinding(string $key): string|int|float|bool|null` - Retrieve bound scalar value (throws NotFoundError if missing)
- `set(string $key, mixed $value): self` - Set any value in the entries container with dot notation support
- `get(string $id): mixed` - PSR-11 method: resolve service from container with automatic autowiring
- `has(string $id): bool` - PSR-11 method: check if service exists in container

**Dependencies:**
- `AutowireContract` (defaulting to Autowire) for dependency resolution
- `ArrayContract` (defaulting to DotContainer) for storing entries
- `Str::isClassString()` helper for detecting class name strings

**Architecture:**
The Container separates concerns by using two internal storages:
1. `$bindings` - Simple scalar values (bind/getBinding)
2. `$entries` - Complex values and service definitions (set/get/has)

The `get()` method implements smart resolution:
1. If entry doesn't exist → autowire the class
2. If entry value is a class string → autowire that class
3. Otherwise → return the stored value

This allows both explicit service definitions and automatic resolution to coexist seamlessly.

**Usage:**
```php
$container = new Container();

// Bind scalar configuration
$container->bind('api.key', 'secret-key-123');

// Set service definitions
$container->set(UserService::class, UserService::class);

// Automatic autowiring (no registration needed)
$service = $container->get(SomeClass::class); // Just works!
```

### Autowire

**Location:** `src/Larafony/Container/Resolvers/Autowire.php:20`

**Purpose:** Automatically resolve and inject dependencies by analyzing class constructors via reflection.

**Key Methods:**
- `instantiate(string $className): object` - Autowire and instantiate a class with all dependencies

**Resolution Strategy (5-step cascade):**
1. Check if parameter name exists in container → use that value
2. Check if parameter type exists in container → use that value
3. Check if parameter has default value → use default
4. Check if parameter allows null → inject null
5. Check if type is built-in (int, string, etc.) → use type default (0, '', false, etc.)
6. Check if type is a class → recursively instantiate that class
7. Otherwise → throw NotFoundError

**Dependencies:**
- `ContainerInterface` for retrieving registered services
- `ReflectionResolverContract` for analyzing parameters

**Example:**
```php
class ComplexService
{
    public function __construct(
        private Database $db,           // Step 6: Autowire Database
        private ?Logger $logger,        // Step 4: Inject null if not registered
        private string $name = 'app',   // Step 3: Use default 'app'
        private int $timeout = 30,      // Step 3: Use default 30
    ) {}
}

$autowire = new Autowire($container);
$service = $autowire->instantiate(ComplexService::class);
// All parameters resolved automatically!
```

### ReflectionResolver

**Location:** `src/Larafony/Container/Resolvers/ReflectionResolver.php:14`

**Purpose:** Reflection-based utility for analyzing class constructors and parameter metadata.

**Key Methods:**
- `getConstructorParameters(string $className): array<ReflectionParameter>` - Get constructor parameters
- `getParameterType(ReflectionParameter $parameter): ?string` - Extract type name from parameter
- `hasDefaultValue(ReflectionParameter $parameter): bool` - Check if parameter has default
- `getDefaultValue(ReflectionParameter $parameter): mixed` - Get default value
- `allowsNull(ReflectionParameter $parameter): bool` - Check if parameter accepts null
- `getDefaultValueForBuiltInType(string $type): mixed` - Get type-appropriate default (0, '', false, [], 0.0)

**Dependencies:** PHP Reflection API (ReflectionClass, ReflectionParameter, ReflectionNamedType)

**Design Philosophy:**
Encapsulates all reflection logic in one place, separating type introspection from dependency resolution. This makes Autowire testable by allowing a mock ReflectionResolverContract.

### ServiceProvider

**Location:** `src/Larafony/Container/ServiceProvider.php:10`

**Purpose:** Abstract base class for organizing service registrations with lifecycle methods.

**Key Methods:**
- `providers(): array<int|string, class-string>` - Return array of services this provider manages
- `register(ContainerContract $container): self` - Register services into the container

**Registration Format:**
```php
return [
    SomeClass::class,                    // Integer key: self-binding
    InterfaceA::class => ClassA::class,  // String key: interface binding
];
```

**Lifecycle:**
1. `providers()` - Define services
2. `register()` - Bind services to container
3. `boot()` - Bootstrap/initialize services (implemented by child classes)

**Usage:**
```php
class MyServiceProvider extends ServiceProvider
{
    public function providers(): array
    {
        return [
            LoggerInterface::class => FileLogger::class,
        ];
    }

    public function boot(ContainerContract $container): void
    {
        // Initialization logic
    }
}
```

### DotContainer

**Location:** `src/Larafony/Container/Helpers/DotContainer.php:13`

**Purpose:** ArrayObject extension providing dot notation access to nested arrays.

**Key Methods:**
- `get(string $key, mixed $default = null): mixed` - Get value using dot notation
- `set(string $key, mixed $value): void` - Set value using dot notation
- `has(string $key): bool` - Check if key exists

**Dependencies:** ArrayGet and ArraySet helper classes for dot notation parsing

**Examples:**
```php
$container = new DotContainer([
    'app' => ['name' => 'Larafony', 'version' => '1.0']
]);

$container->get('app.name'); // 'Larafony'
$container->set('app.debug', true);
$container->has('app.debug'); // true
```

## Comparison with Other Frameworks

| Feature | Larafony | Laravel | Symfony |
|---------|----------|---------|---------|
| **PSR-11 Compliance** | Full PSR-11 implementation | PSR-11 compliant | PSR-11 since Symfony 3.3 |
| **Autowiring** | Built-in with 5-step resolution | Automatic in many contexts | Opt-in via autowire: true |
| **Service Providers** | Abstract class with providers() array | ServiceProvider classes | Service configuration files |
| **Configuration** | Code-based with ServiceProviders | Array-based config files | YAML/XML/PHP config |
| **Zero Config** | Autowire any class automatically | Controllers, listeners auto-injected | Requires service definition |
| **Reflection** | Custom ReflectionResolver | Container uses reflection | ContainerBuilder compiles |
| **Dot Notation** | DotContainer for nested access | Arr::get() helper | Parameter bags |
| **Interface Binding** | set(Interface::class, Class::class) | bind() / singleton() | services.yaml aliases |
| **Dependencies** | psr/container only | illuminate/container | symfony/dependency-injection |

**Key Differences:**

- **PSR-First Design:** Larafony builds directly on PSR-11 with ContainerContract extension. Laravel's container predates PSR-11 and implements it for compatibility. Symfony has been PSR-11 compliant since version 3.3.

- **Code-Based Configuration:** Larafony uses ServiceProvider classes with `providers()` method returning arrays for service registration. Laravel uses ServiceProviders with manual bind() calls. Symfony uses YAML/XML configuration files.

- **Zero Configuration Autowiring:** Larafony autowires any class automatically with intelligent 5-step resolution. Laravel autowires in specific contexts (controllers, listeners, jobs). Symfony requires explicit service definitions unless autowire is enabled globally.

- **Built-in Reflection Layer:** Larafony separates reflection logic into ReflectionResolver contract for testability. Laravel's container directly uses PHP reflection. Symfony compiles containers at build time.

- **Minimal Dependencies:** Larafony only requires `psr/container` interface. Laravel's illuminate/container has multiple dependencies. Symfony's dependency-injection component has psr/container but is part of larger framework.

- **Dot Notation Support:** Larafony includes DotContainer extending ArrayObject for nested configuration. Laravel provides Arr::get() helper functions. Symfony uses ParameterBag objects.

- **Interface Resolution:** Larafony uses `set(Interface::class, Concrete::class)`. Laravel uses `bind()` or `singleton()` methods. Symfony uses service aliases in YAML configuration.

- **Smart Resolution:** Larafony's Container::get() detects if value is a class string and autowires automatically. Laravel requires explicit binding types (bind vs singleton vs instance). Symfony resolves based on service definitions.

## Real World Integration

This chapter's features are demonstrated in the demo application by refactoring the error handler initialization to use the Container and ServiceProvider pattern, showing practical dependency injection in action.

### Demo Application Changes

The demo application was refactored to use the new Container and ServiceProvider system instead of directly instantiating the error handler. This demonstrates:
- Container initialization as application entry point
- ServiceProvider pattern for organizing framework services
- Automatic dependency resolution
- Service lifecycle (register → boot)

### File Structure
```
demo-app/
└── public/
    └── index.php          # Refactored to use Container and ErrorHandlerServiceProvider
```

### Implementation Example

**File: `demo-app/public/index.php`**

```php
<?php

declare(strict_types=1);

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Clock\Enums\TimeFormat;
use Larafony\Framework\Clock\Enums\Timezone;
use Larafony\Framework\Container\Container;
use Larafony\Framework\ErrorHandler\ServiceProviders\ErrorHandlerServiceProvider;
use Uri\Rfc3986\Uri;

require_once __DIR__ . '/../vendor/autoload.php';

// NEW in Chapter 4: Container-based application bootstrap
// Create the DI container as the foundation of the application
$container = new Container();

// NEW in Chapter 4: Service Provider pattern for organizing services
// Register the ErrorHandler through its service provider
// This demonstrates the two-phase lifecycle: register() then boot()
new ErrorHandlerServiceProvider()
    ->register($container)  // Phase 1: Register service definition in container
    ->boot($container);     // Phase 2: Retrieve and initialize the service

// Simple routing (continues from Chapter 3)
$path = new Uri($_SERVER['REQUEST_URI'])->getPath();

match ($path) {
    '/' => handleHome(),
    '/error' => handleError(),
    '/exception' => handleException(),
    '/fatal' => handleFatal(),
    default => handleNotFound(),
};

function handleHome(): void
{
    echo '<h1>Larafony Framework Demo</h1>';
    echo '<p>Error Handler is active. Try these endpoints:</p>';

    // Clock system from Chapter 3 (unchanged)
    echo '<p>Now is ' . ClockFactory::timezone(Timezone::EUROPE_WARSAW)
        ->format(TimeFormat::DATETIME) . '</p>';

    echo '<ul>';
    echo '<li><a href="/error">Trigger E_WARNING</a></li>';
    echo '<li><a href="/exception">Trigger Exception</a></li>';
    echo '<li><a href="/fatal">Trigger Fatal Error</a></li>';
    echo '</ul>';
}

// ... other handlers unchanged ...
```

**What's happening here:**

1. **Container Initialization** (line 14): Creates the dependency injection container that will manage all application services. This becomes the central registry for the entire application.

2. **Import ServiceProvider** (line 9): The `ErrorHandlerServiceProvider` encapsulates all logic for registering and bootstrapping the error handler service, following the Single Responsibility Principle.

3. **Service Provider Registration** (line 17-19): Demonstrates the two-phase service provider lifecycle:
   - `register($container)` - Registers `DetailedErrorHandler::class` into the container (defined in ErrorHandlerServiceProvider::providers())
   - `boot($container)` - Retrieves the error handler from container and calls its `register()` method to activate it

4. **Method Chaining** (lines 17-19): The ServiceProvider returns `$this` from `register()`, allowing fluent interface: `->register($container)->boot($container)`

5. **Separation of Concerns**: Compare to Chapter 3's `new DetailedErrorHandler()->register()`:
   - **Before:** Direct instantiation, tight coupling, no dependency injection capability
   - **After:** Container-managed, loose coupling, ready for dependency injection if ErrorHandler needs services

6. **Container as Foundation**: While this simple example doesn't show complex dependencies, the container is now in place for future services (routing, database, etc.) to use dependency injection.

**Key Refactoring:**

```diff
- use Larafony\Framework\ErrorHandler\DetailedErrorHandler;
+ use Larafony\Framework\Container\Container;
+ use Larafony\Framework\ErrorHandler\ServiceProviders\ErrorHandlerServiceProvider;

- new DetailedErrorHandler()->register();
+ $container = new Container();
+ new ErrorHandlerServiceProvider()->register($container)->boot($container);
```

### ErrorHandlerServiceProvider Implementation

**File: `src/Larafony/ErrorHandler/ServiceProviders/ErrorHandlerServiceProvider.php`**

```php
<?php

declare(strict_types=1);

namespace Larafony\Framework\ErrorHandler\ServiceProviders;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Container\ServiceProvider;
use Larafony\Framework\ErrorHandler\DetailedErrorHandler;

class ErrorHandlerServiceProvider extends ServiceProvider
{
    /**
     * Define services this provider manages
     * Returns array where values are class names to register
     *
     * @return array<int|string, class-string>
     */
    public function providers(): array
    {
        // Register DetailedErrorHandler as a self-bound service
        // Integer key means: $container->set(DetailedErrorHandler::class, DetailedErrorHandler::class)
        return [DetailedErrorHandler::class];
    }

    /**
     * Register phase: Store service definitions in container
     * Parent implementation loops through providers() and calls $container->set()
     */
    #[\Override]
    public function register(ContainerContract $container): self
    {
        parent::register($container);
        return $this;
    }

    /**
     * Boot phase: Initialize and activate services
     * This is where services are retrieved from container and configured
     */
    public function boot(ContainerContract $container): void
    {
        // Retrieve the DetailedErrorHandler from container
        // Container autowires it if needed (though it has no dependencies)
        /** @var DetailedErrorHandler $item */
        $item = $container->get(DetailedErrorHandler::class);

        // Activate the error handler by registering it with PHP
        $item->register();
    }
}
```

**What's happening here:**

1. **Extends ServiceProvider** (line 11): Inherits the base provider logic for automatic service registration from the `providers()` array.

2. **providers() Method** (lines 18-22): Returns array of services to register. Using integer key (no explicit key) means self-binding: the class is both the identifier and the concrete implementation.

3. **register() Phase** (lines 28-32): Calls parent's register() which iterates through providers() and calls `$container->set(DetailedErrorHandler::class, DetailedErrorHandler::class)`. This stores the definition but doesn't instantiate yet.

4. **boot() Phase** (lines 38-46):
   - Calls `$container->get(DetailedErrorHandler::class)` which triggers autowiring
   - Since DetailedErrorHandler has no constructor dependencies, it's simply instantiated
   - Calls `$item->register()` to activate error handling

5. **Separation of Registration and Initialization**: This pattern allows:
   - **Register phase:** All services defined (fast, no instantiation)
   - **Boot phase:** Services initialized in correct order with access to all registered services
   - **Lazy loading:** Services only instantiated when needed (not shown here but enabled by architecture)

### Running the Demo

```bash
cd framework/demo-app
php8.5 -S localhost:8000 -t public
```

Then visit:
- `http://localhost:8000/` - See homepage with error handler active

**Expected output:**

The homepage displays normally with error handler protection:

```
Larafony Framework Demo

Error Handler is active. Try these endpoints:

Now is 2025-10-22 17:30:45

• Trigger E_WARNING
• Trigger Exception
• Trigger Fatal Error
```

The difference from Chapter 3 is invisible to the user but architectural:
- Error handler is now container-managed
- Application uses dependency injection foundation
- Services organized through provider pattern
- Ready to scale to complex service dependencies

### Key Takeaways

- **Foundation for Scalability**: While the demo shows simple service registration, the Container infrastructure is now in place for complex dependency graphs (repositories, services, middleware, etc.)

- **Service Provider Pattern**: Organizes related services together (ErrorHandlerServiceProvider) rather than scattering registrations across bootstrap files

- **Two-Phase Lifecycle**: `register()` defines services quickly, `boot()` initializes them - this allows circular dependency resolution and ordered initialization

- **PSR-11 Compliance**: The container implements standard interface, making it compatible with any PSR-11 aware libraries

- **Zero Configuration Ready**: While this example uses explicit provider, the container can autowire any class automatically without registration

- **Architectural Evolution**: Compare the progression:
  - **Chapter 2:** Direct instantiation `new DetailedErrorHandler()`
  - **Chapter 4:** Provider pattern with DI `ErrorHandlerServiceProvider()->register($container)->boot($container)`
  - **Future:** Full application with dozens of services all auto-wired through the container

- **Testability Improvement**: Container can be mocked or swapped with test double, and services can be replaced with test implementations easily

---

📚 **Learn More:** This implementation is explained in detail with step-by-step tutorials, tests, and best practices at [masterphp.eu](https://masterphp.eu)
