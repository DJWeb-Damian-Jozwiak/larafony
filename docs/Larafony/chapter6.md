# Chapter 6: Web Application & Routing

This chapter introduces the web application layer and routing system, enabling HTTP request handling with a clean, expressive API.

## Table of Contents

- [Overview](#overview)
- [Web Application](#web-application)
- [HTTP Kernel](#http-kernel)
- [Routing System](#routing-system)
- [Route Handlers](#route-handlers)
- [Testing](#testing)

## Overview

The Web and Routing packages provide:

- **Application Singleton**: Central application instance with service provider support
- **HTTP Kernel**: PSR-15 compliant request handler with header and redirect management
- **Basic Router**: Simple, fast routing with multiple handler types
- **Route Matching**: Path and method-based route resolution
- **Handler Factory Pattern**: Flexible route handler creation supporting closures, class methods, invocable classes, and functions

## Web Application

### Application Class

The `Application` class is a singleton that extends the Container and serves as the central coordination point for the framework.

```php
use Larafony\Framework\Web\Application;

// Get application instance
$app = Application::instance('/var/www/app');

// Access base path
echo $app->base_path; // /var/www/app

// Register service providers
$app->withServiceProviders([
    HttpServiceProvider::class,
    RouteServiceProvider::class,
]);

// Define routes
$app->withRoutes(function (Router $router) {
    $router->addRouteByParams('GET', '/', HomeController::class . '@index');
    $router->addRouteByParams('POST', '/users', [UserController::class, 'store']);
});

// Run the application
$app->run();
```

**Key Features:**

- **Singleton Pattern**: Ensures single application instance
- **Base Path Support**: Optional base path configuration
- **Service Provider Integration**: Fluent API for registering providers
- **Route Definition**: Convenient route registration
- **PSR-11 Container**: Full dependency injection support

### HTTP Kernel

The `Kernel` class handles HTTP request processing following PSR-15 standards.

```php
use Larafony\Framework\Web\Kernel;
use Larafony\Framework\Routing\Basic\Router;

$kernel = new Kernel($router);

// Handle a request
$response = $kernel->handle($request);

// With custom exit callback (useful for testing)
$response = $kernel->handle($request, fn($code) => throw new ExitException($code));
```

**Request Processing Pipeline:**

1. **Route Resolution**: Find matching route using the router
2. **Header Handling**: Set HTTP response code and headers
3. **Redirect Management**: Handle Location headers with proper status codes

**Key Methods:**

- `handle(ServerRequestInterface $request, ?callable $exitCallback = null): ResponseInterface`
  - Main request handler following PSR-15
  - Optional exit callback for testing

- `handleHeaders(ResponseInterface $response): ResponseInterface`
  - Sets HTTP response code
  - Outputs headers (except Location)
  - Returns response unchanged

- `handleRedirects(ResponseInterface $response, ?callable $callback = null): ResponseInterface`
  - Detects Location header
  - Validates/corrects redirect status codes (300-399)
  - Calls exit callback (defaults to `exit()`)

## Routing System

### Basic Router

The router provides simple, expressive route definition with multiple handler types.

```php
use Larafony\Framework\Routing\Basic\Router;

$router = $app->get(Router::class);

// Closure handler
$router->addRouteByParams('GET', '/', function ($request) {
    return new Response(200, 'Hello World');
});

// Class method notation (string)
$router->addRouteByParams('GET', '/users', 'UserController@index');

// Array notation [class, method]
$router->addRouteByParams('POST', '/users', [UserController::class, 'store']);

// Named routes
$router->addRouteByParams('GET', '/profile', ProfileController::class, 'profile.show');
```

### Route Definition

Routes are defined using the `Route` class, which encapsulates path, method, and handler information.

```php
use Larafony\Framework\Routing\Basic\Route;
use Larafony\Framework\Http\Enums\HttpMethod;

$route = new Route(
    path: '/users/{id}',
    method: HttpMethod::GET,
    handlerDefinition: [UserController::class, 'show'],
    factory: $handlerFactory,
    name: 'users.show'
);

// Handle request
$response = $route->handle($request);
```

**Route Properties:**

- `path`: URL path pattern
- `method`: HTTP method (GET, POST, PUT, DELETE, etc.)
- `handler`: Resolved request handler (PSR-15)
- `name`: Optional route name for URL generation

### Route Collection

The `RouteCollection` manages all registered routes and performs route matching.

```php
use Larafony\Framework\Routing\Basic\RouteCollection;

$collection = $app->get(RouteCollection::class);

// Add routes
$collection->addRoute($route);

// Find matching route
try {
    $matchedRoute = $collection->findRoute($request);
    $response = $matchedRoute->handle($request);
} catch (RouteNotFoundError $e) {
    // Handle 404
}
```

### Route Matcher

The `RouteMatcher` class handles route matching logic.

```php
use Larafony\Framework\Routing\Basic\RouteMatcher;

$matcher = new RouteMatcher();

if ($matcher->matches($request, $route)) {
    // Route matches the request
}
```

**Matching Rules:**

- Path matching with trailing slash normalization
- Case-insensitive HTTP method matching
- Both path AND method must match

## Route Handlers

The framework supports four types of route handlers through a factory pattern.

### Handler Types

#### 1. Closure Handler

```php
$router->addRouteByParams('GET', '/', function (ServerRequestInterface $request) {
    $factory = new ResponseFactory();
    return $factory->createResponse(200)
        ->withBody($streamFactory->createStream('Welcome!'));
});
```

#### 2. Class Method Handler

```php
// String notation
$router->addRouteByParams('GET', '/users', 'UserController@index');

// Array notation
$router->addRouteByParams('GET', '/users', [UserController::class, 'index']);

// Controller class
class UserController
{
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        // Return list of users
    }
}
```

#### 3. Invocable Class Handler

```php
$router->addRouteByParams('GET', '/dashboard', DashboardController::class);

// Invocable controller
class DashboardController
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        // Return dashboard view
    }
}
```

#### 4. Function Handler

```php
$router->addRouteByParams('GET', '/health', 'health_check');
$router->addRouteByParams('GET', '/health', health_check(...));

function health_check(ServerRequestInterface $request): ResponseInterface
{
    return (new ResponseFactory())->createResponse(200)
        ->withBody((new StreamFactory())->createStream('OK'));
}
```

### Handler Factory

The `RouteHandlerFactory` automatically detects and creates the appropriate handler type.

```php
use Larafony\Framework\Routing\Basic\RouteHandlerFactory;

$factory = new RouteHandlerFactory($arrayFactory, $stringFactory);

// Automatically detects type and creates handler
$handler = $factory->create($handlerDefinition);
```

**Factory Delegation:**

- `ArrayHandlerFactory`: Handles `[Class, 'method']` notation
- `StringHandlerFactory`: Handles string notation and function names
  - `'Class@method'` → ClassMethodRouteHandler
  - `'Class'` (invocable) → InvocableClassRouteHandler
  - `'function_name'` → FunctionRouteHandler

## Error Handling

### RouteNotFoundError

Thrown when no matching route is found for a request.

```php
use Larafony\Framework\Routing\Exceptions\RouteNotFoundError;

try {
    $route = $collection->findRoute($request);
    $response = $route->handle($request);
} catch (RouteNotFoundError $e) {
    // Exception message: "Route for GET /nonexistent not found"
    // Return 404 response
}
```

## Service Providers

### RouteServiceProvider

Registers routing-related services in the container.

```php
use Larafony\Framework\Routing\ServiceProviders\RouteServiceProvider;

$app->withServiceProviders([RouteServiceProvider::class]);

// Now available in container:
// - RequestHandlerInterface → Router
// - ArrayHandlerFactory
// - StringHandlerFactory
```

## Complete Example

```php
<?php

use Larafony\Framework\Web\Application;
use Larafony\Framework\Routing\Basic\Router;
use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Http\ServiceProviders\HttpServiceProvider;
use Larafony\Framework\Routing\ServiceProviders\RouteServiceProvider;
use Psr\Http\Message\ServerRequestInterface;

// Initialize application
$app = Application::instance(__DIR__);

// Register service providers
$app->withServiceProviders([
    HttpServiceProvider::class,
    RouteServiceProvider::class,
]);

// Define routes
$app->withRoutes(function (Router $router) {
    // Home page
    $router->addRouteByParams('GET', '/', function (ServerRequestInterface $request) {
        $response = (new ResponseFactory())->createResponse(200);
        return $response->withBody(
            (new StreamFactory())->createStream('Welcome to Larafony!')
        );
    }, 'home');

    // API routes
    $router->addRouteByParams('GET', '/api/users', [UserController::class, 'index']);
    $router->addRouteByParams('POST', '/api/users', [UserController::class, 'store']);
    $router->addRouteByParams('GET', '/api/users/{id}', [UserController::class, 'show']);

    // Admin dashboard (invocable)
    $router->addRouteByParams('GET', '/admin', AdminDashboard::class);
});

// Run application
$app->run();
```

## Testing

### Base TestCase

All tests extend `Larafony\Framework\Tests\TestCase`, which automatically cleans up the Application singleton:

```php
use Larafony\Framework\Tests\TestCase;
use Larafony\Framework\Web\Application;

class MyTest extends TestCase
{
    // Application is automatically reset before/after each test
    public function testSomething(): void
    {
        $app = Application::instance();
        // Test your code
    }
}
```

### Testing Routes

```php
use Larafony\Framework\Tests\TestCase;
use Larafony\Framework\Web\Application;
use Larafony\Framework\Routing\Basic\Router;

class RouteTest extends TestCase
{
    public function testHomeRoute(): void
    {
        $app = Application::instance();

        $app->withRoutes(function (Router $router) {
            $router->addRouteByParams('GET', '/', function ($request) {
                return (new ResponseFactory())->createResponse(200);
            });
        });

        $router = $app->get(Router::class);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $response = $router->handle($request);

        $this->assertSame(200, $response->getStatusCode());
    }
}
```

### Testing Handlers

```php
public function testControllerHandler(): void
{
    $app = Application::instance();
    $factory = $app->get(RouteHandlerFactory::class);

    $handler = $factory->create([HomeController::class, 'index']);
    $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
    $response = $handler->handle($request);

    $this->assertInstanceOf(ResponseInterface::class, $response);
}
```

## Code Coverage

The Web and Routing packages have **100% test coverage**:

### Web Package
- `Application`: 100% (6/6 methods, 19/19 lines)
- `Kernel`: 100% (5/5 methods, 24/24 lines)

### Routing Package
- `Router`: 100% (4/4 methods, 13/13 lines)
- `Route`: 100% (2/2 methods, 2/2 lines)
- `RouteCollection`: 100% (3/3 methods, 8/8 lines)
- `RouteMatcher`: 100% (3/3 methods, 6/6 lines)
- `RouteHandlerFactory`: 100% (3/3 methods, 5/5 lines)
- All Handlers: 100%
- All Factories: 100%
- `RouteNotFoundError`: 100%
