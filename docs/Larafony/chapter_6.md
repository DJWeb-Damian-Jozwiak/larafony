# Chapter 6: Basic Routing

> Part of the Larafony Framework - A modern PHP 8.5 framework built from scratch
>
> 📚 Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)

## Overview

Chapter 6 implements a basic routing system for the Larafony framework, providing URL-to-controller mapping with multiple handler types and PSR-15 request handler compliance. This component bridges incoming HTTP requests to application logic through a clean, type-safe routing layer.

The implementation focuses on flexibility and PSR standards. Routes can be defined with closures, controller methods, invocable classes, or function names. The router uses a factory pattern to convert various handler definitions into uniform PSR-15 RequestHandlerInterface implementations, enabling consistent request processing regardless of handler type.

The architecture introduces **Router** as the main PSR-15 request handler, **RouteCollection** for storing and matching routes, **Route** as immutable route definitions with lazy handler instantiation, **RouteHandlerFactory** for converting handler definitions to PSR-15 handlers, **RouteMatcher** for path and method matching, and **Kernel** as the HTTP kernel using PHP 8.5's pipe operator for response processing. The system includes specialized handlers for each route type (ClosureRouteHandler, ClassMethodRouteHandler, InvocableClassRouteHandler, FunctionRouteHandler) and integrates seamlessly with the Application and Container from previous chapters.

## Key Components

### Core Routing

- **Router** - PSR-15 request handler dispatching requests to matched routes (implements RequestHandlerInterface, uses RouteCollection for route storage)
- **Route** - Immutable route definition with path, HTTP method, and handler (uses RouteHandlerFactory to create PSR-15 handlers from various definition types)
- **RouteCollection** - Container storing routes and finding matches (uses RouteMatcher for path/method comparison)
- **RouteMatcher** - Matcher comparing request path/method with route definitions

### Handler Factories and Types

- **RouteHandlerFactory** - Main factory creating PSR-15 handlers from closures, arrays, or strings (delegates to StringHandlerFactory and ArrayHandlerFactory)
- **ClosureRouteHandler, ClassMethodRouteHandler, InvocableClassRouteHandler, FunctionRouteHandler** - Specialized PSR-15 handlers for different route definition types (all implement RequestHandlerInterface)

### HTTP Kernel

- **Kernel** - HTTP kernel processing requests through router with header/redirect handling (uses PHP 8.5 pipe operator for response pipeline)
- **Application::withRoutes()** - Fluent route registration via callback receiving Router instance

### Enums and Exceptions

- **HttpMethod** - Enum for HTTP methods (GET, POST, PUT, DELETE, PATCH, OPTIONS, HEAD)
- **RouteNotFoundError** - Exception thrown when no route matches request

## PSR Standards Implemented

- **PSR-15**: HTTP Server Request Handlers - Router, Route, and all handler classes implement RequestHandlerInterface
- **PSR-7**: HTTP Message Interface - All handlers accept ServerRequestInterface and return ResponseInterface
- **PSR-11**: Container Interface - Router and handlers use Container for dependency injection
- **Type Safety**: Strict typing with backed HttpMethod enum and typed handler signatures

## New Attributes

This chapter doesn't introduce new PHP attributes, but extensively uses PHP 8.5 features:

- **Pipe operator** (`|>`) in Kernel for response processing pipeline
- `private(set)` in Router for asymmetric visibility of RouteCollection
- `enum HttpMethod` backed by string for HTTP methods
- Constructor property promotion throughout
- First-class callable syntax (e.g., `exit(...)`)
- Union types for handler definitions: `\Closure|array|string`

## Usage Examples

### Basic Example - Simple Routes

```php
<?php

use Larafony\Framework\Routing\Basic\Router;
use Larafony\Framework\Routing\Basic\RouteCollection;
use Larafony\Framework\Container\Container;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

$container = new Container();
$router = new Router(new RouteCollection(), $container);

// Closure route
$router->addRouteByParams('GET', '/', function (ServerRequestInterface $request): ResponseInterface {
    $factory = new ResponseFactory();
    return $factory->createResponse(200)->withContent('Homepage');
});

// Controller method route
$router->addRouteByParams('GET', '/users', [UserController::class, 'index']);

// Invocable controller route
$router->addRouteByParams('POST', '/users', InvocableUserController::class);

// Function route
$router->addRouteByParams('GET', '/about', 'handleAboutPage');

// Handle request
$request = $serverRequestFactory->fromGlobals();
$response = $router->handle($request);
```

### Advanced Example - Application with Routing

```php
<?php

use Larafony\Framework\Web\Application;
use Larafony\Framework\Routing\Basic\Router;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PostController;

$app = Application::instance(__DIR__);

// Register service providers
$app->withServiceProviders([
    ErrorHandlerServiceProvider::class,
    HttpServiceProvider::class,
    RouteServiceProvider::class, // Registers Router and related services
]);

// Define routes via callback
$app->withRoutes(function (Router $router): void {
    // Homepage
    $router->addRouteByParams('GET', '/', [HomeController::class, 'index']);

    // Users
    $router->addRouteByParams('GET', '/users', [UserController::class, 'index']);
    $router->addRouteByParams('GET', '/users/create', [UserController::class, 'create']);
    $router->addRouteByParams('POST', '/users', [UserController::class, 'store']);

    // Posts
    $router->addRouteByParams('GET', '/posts', [PostController::class, 'index']);
    $router->addRouteByParams('GET', '/posts/{id}', [PostController::class, 'show']);

    // API endpoints with closure
    $router->addRouteByParams('GET', '/api/status', fn() =>
        (new ResponseFactory())->createResponse(200)->withJson(['status' => 'ok'])
    );
});

// Run application (handles request and emits response)
$app->run();
```

### Route Handler Types Example

```php
<?php

use Larafony\Framework\Routing\Basic\Router;

// 1. Closure Handler
$router->addRouteByParams('GET', '/closure', function (ServerRequestInterface $request) {
    return $responseFactory->createResponse(200)->withContent('Closure route');
});

// 2. Controller Method Handler [ClassName, methodName]
$router->addRouteByParams('GET', '/controller', [UserController::class, 'index']);

// 3. Invocable Controller Handler (class with __invoke)
class WelcomeController {
    public function __invoke(ServerRequestInterface $request): ResponseInterface {
        return $responseFactory->createResponse(200)->withContent('Welcome!');
    }
}
$router->addRouteByParams('GET', '/welcome', WelcomeController::class);

// 4. Function Handler (string function name)
function aboutPage(ServerRequestInterface $request): ResponseInterface {
    return $responseFactory->createResponse(200)->withContent('About page');
}
$router->addRouteByParams('GET', '/about', 'aboutPage');
```

### HTTP Kernel Pipeline Example

```php
<?php

use Larafony\Framework\Web\Kernel;
use Larafony\Framework\Routing\Basic\Router;

$kernel = new Kernel($router);

// Handle request through kernel
// Kernel uses pipe operator for response processing:
// 1. Router handles request
// 2. handleHeaders() sets HTTP status and headers
// 3. handleRedirects() processes Location header if present
$response = $kernel->handle($request);

// The pipe operator transforms this:
// $response = $this->router->handle($request)
//     |> $this->handleHeaders(...)
//     |> (fn ($r) => $this->handleRedirects($r, $exitCallback));

// Into a clean pipeline without intermediate variables
```

## Implementation Details

### Router

**Location:** `src/Larafony/Routing/Basic/Router.php:13`

**Purpose:** Main routing component implementing PSR-15 RequestHandlerInterface to dispatch requests to matched routes.

**Key Methods:**
- `addRoute(Route $route): self` - Add route to collection
- `addRouteByParams(string $method, string $path, \Closure|array $handler, ?string $name = null): self` - Convenient route registration
- `handle(ServerRequestInterface $request): ResponseInterface` - PSR-15 method dispatching request to matched route

**Dependencies:**
- RouteCollection (stores routes)
- ContainerContract (for dependency injection in handlers)

**Asymmetric Visibility:**
Uses `private(set)` for $routes property - publicly readable via `$router->routes`, privately writable

**Usage:**
```php
$router = new Router($collection, $container);

$router->addRouteByParams('GET', '/users', [UserController::class, 'index']);

$response = $router->handle($request); // Finds route and dispatches
```

### Route

**Location:** `src/Larafony/Routing/Basic/Route.php:12`

**Purpose:** Immutable value object representing a single route with lazy handler instantiation.

**Key Properties:**
- `string $path` - URL path (/users, /posts/{id}, etc.)
- `HttpMethod $method` - HTTP method enum (GET, POST, etc.)
- `?string $name` - Optional route name for URL generation
- `RequestHandlerInterface $handler` - PSR-15 handler (readonly, created by factory)

**Handler Definition Types:**
- `\Closure` - Anonymous function
- `array{class-string, string}` - [ClassName::class, 'methodName']
- `string` - Invocable class name or function name

**Lazy Instantiation:**
Handler is created in constructor via RouteHandlerFactory, not when route is matched. This enables early validation.

**Usage:**
```php
$route = new Route(
    path: '/users',
    method: HttpMethod::GET,
    handlerDefinition: [UserController::class, 'index'],
    factory: $handlerFactory,
    name: 'users.index'
);

$response = $route->handle($request); // Delegates to handler
```

### RouteHandlerFactory

**Location:** `src/Larafony/Routing/Basic/RouteHandlerFactory.php:11`

**Purpose:** Factory creating PSR-15 RequestHandlerInterface from various handler definition types.

**Strategy:**
- Closure → ClosureRouteHandler
- Array → ArrayHandlerFactory (delegates to ClassMethodRouteHandler)
- String → StringHandlerFactory (delegates to InvocableClassRouteHandler or FunctionRouteHandler)

**Dependencies:**
- ArrayHandlerFactory for array definitions
- StringHandlerFactory for string definitions
- Container for dependency injection

**Type Safety:**
Each handler type enforces correct signature via type hints

**Usage:**
```php
$factory = new RouteHandlerFactory($arrayFactory, $stringFactory);

// Returns ClosureRouteHandler
$handler = $factory->create(fn($req) => $response);

// Returns ClassMethodRouteHandler
$handler = $factory->create([UserController::class, 'index']);

// Returns InvocableClassRouteHandler or FunctionRouteHandler
$handler = $factory->create('handleRequest');
```

### RouteCollection

**Location:** `src/Larafony/Routing/Basic/RouteCollection.php:10`

**Purpose:** Container storing routes and finding matches based on request.

**Key Methods:**
- `addRoute(Route $route): void` - Add route to collection
- `findRoute(ServerRequestInterface $request): Route` - Find matching route or throw RouteNotFoundError
- `getRoutes(): array` - Get all registered routes

**Dependencies:** RouteMatcher for comparing request with routes

**Matching Logic:**
Iterates through routes, uses RouteMatcher to check path and method match

**Exception:**
Throws RouteNotFoundError if no route matches

**Usage:**
```php
$collection = new RouteCollection();
$collection->addRoute($route1);
$collection->addRoute($route2);

try {
    $route = $collection->findRoute($request);
} catch (RouteNotFoundError $e) {
    // Handle 404
}
```

### Kernel

**Location:** `src/Larafony/Web/Kernel.php:12`

**Purpose:** HTTP kernel processing requests through router with response post-processing using PHP 8.5 pipe operator.

**Key Methods:**
- `handle(ServerRequestInterface $request, ?callable $exitCallback = null): ResponseInterface` - Main request handling
- `handleHeaders(ResponseInterface $response): ResponseInterface` - Set HTTP headers (excluding Location)
- `handleRedirects(ResponseInterface $response, ?callable $callback = null): ResponseInterface` - Process redirects
- `withRoutes(callable $callback): self` - Register routes via callback

**Pipe Operator Usage:**
```php
return $this->router->handle($request)
    |> $this->handleHeaders(...)
    |> (fn (ResponseInterface $response) => $this->handleRedirects($response, $exitCallback));
```

This creates a pipeline:
1. Router handles request → ResponseInterface
2. Pipe to handleHeaders() → ResponseInterface with headers set
3. Pipe to handleRedirects() → ResponseInterface (exits if redirect)

**Redirect Handling:**
- Checks for Location header
- Ensures status code is 3xx (defaults to 302 if not)
- Calls exit callback (defaults to `exit(...)`)

**Usage:**
```php
$kernel = new Kernel($router);

$kernel->withRoutes(function ($router) {
    $router->addRouteByParams('GET', '/', [HomeController::class, 'index']);
});

$response = $kernel->handle($request); // Pipeline: route → headers → redirects
```

### HttpMethod Enum

**Location:** `src/Larafony/Http/Enums/HttpMethod.php:7`

**Purpose:** Type-safe HTTP method representation as backed string enum.

**Cases:**
- GET, POST, PUT, DELETE, PATCH, OPTIONS, HEAD

**Usage:**
```php
$method = HttpMethod::GET;
echo $method->value; // 'GET'

$method = HttpMethod::from('POST'); // From string
```

## Comparison with Other Frameworks

| Feature | Larafony | Laravel | Symfony |
|---------|----------|---------|---------|
| **PSR-15 Compliance** | Native PSR-15 handlers | Not PSR-15 compliant | PSR-15 via middleware component |
| **Route Definition** | Router->addRouteByParams() | Route::get/post/put/delete() | Routes defined in YAML/annotations |
| **Handler Types** | Closure, [Class, method], Invocable, Function | Closure, [Class, method], Invocable | Controller::method notation |
| **Route Matching** | RouteMatcher with manual iteration | FastRoute library | Symfony Routing component |
| **HTTP Methods** | Backed enum HttpMethod | String-based | String-based |
| **Route Storage** | RouteCollection class | RouteCollection class | RouteCollection class |
| **Handler Factory** | RouteHandlerFactory with strategy pattern | Controller dispatcher | Controller resolver |
| **Kernel** | Kernel with pipe operator | HTTP Kernel with middleware | HttpKernel component |
| **Dependencies** | PSR-15, PSR-7, PSR-11 | illuminate/routing | symfony/routing |

**Key Differences:**

- **PSR-15 Native:** Larafony's Router, Route, and all handlers implement PSR-15 RequestHandlerInterface. Laravel doesn't implement PSR-15. Symfony provides PSR-15 support but it's not the default.

- **Pipe Operator:** Larafony's Kernel uses PHP 8.5 pipe operator (`|>`) for response processing pipeline. Laravel and Symfony use traditional method chaining or middleware stacks.

- **Type-Safe Methods:** Larafony uses `HttpMethod` enum for type safety. Laravel and Symfony use strings, allowing typos like `"GETT"`.

- **Handler Factory Pattern:** Larafony separates handler creation into RouteHandlerFactory with specialized factories for arrays and strings. Laravel uses controller dispatcher. Symfony uses controller resolver.

- **Asymmetric Visibility:** Larafony's Router uses `private(set)` for $routes - publicly readable, privately writable. Laravel and Symfony use traditional protected properties.

- **No Route Parameters Yet:** This chapter implements basic routing without parameters. Laravel and Symfony support `/users/{id}` patterns. (Larafony will add this in later chapters)

- **Manual Matching:** Larafony uses simple iteration with RouteMatcher. Laravel uses nikic/fast-route for performance. Symfony uses compiled route matchers.

- **Fluent API:** Larafony uses Application::withRoutes() callback. Laravel uses static Route facade. Symfony uses annotations/YAML files.

- **First-Class Callables:** Larafony uses `exit(...)` for first-class callable. Laravel and Symfony use traditional callables.

## Real World Integration

This chapter's features are demonstrated in the demo application with a complete routing refactor, moving from manual path matching to declarative route definitions.

### Demo Application Changes

The demo application was refactored to use the Router system instead of manual match expressions. Route registration moved to bootstrap file, and index.php simplified to just `$app->run()`. This demonstrates:
- Declarative route registration
- Router integration with Application
- Kernel-based request handling
- Clean separation of route definitions from request handling

### File Structure
```
demo-app/
├── bootstrap/
│   └── web_app.php          # Application setup with route registration
└── public/
    └── index.php            # Simplified to single $app->run() call
```

### Implementation Example

**File: `demo-app/bootstrap/web_app.php`**

```php
<?php

declare(strict_types=1);

use App\Http\Controllers\DemoController;
use Larafony\Framework\ErrorHandler\ServiceProviders\ErrorHandlerServiceProvider;
use Larafony\Framework\Http\ServiceProviders\HttpServiceProvider;
use Larafony\Framework\Routing\Basic\Router;
use Larafony\Framework\Routing\ServiceProviders\RouteServiceProvider;

require_once __DIR__ . '/../vendor/autoload.php';

// Create Application singleton
$app = \Larafony\Framework\Web\Application::instance();

// Register service providers
// Each provider registers services into container and boots them
$app->withServiceProviders([
    ErrorHandlerServiceProvider::class,  // Registers error handler
    HttpServiceProvider::class,          // Registers PSR-7/17 factories
    RouteServiceProvider::class,         // NEW: Registers Router, RouteCollection, etc.
]);

// Define routes using fluent API
// withRoutes() callback receives Router instance with all dependencies injected
$app->withRoutes(static function (Router $router): void {
    // Route format: addRouteByParams(method, path, handler, ?name)
    // Handler: [ControllerClass::class, 'methodName'] array

    // Homepage route
    $router->addRouteByParams('GET', '/', [DemoController::class, 'home']);

    // Info endpoint showing request data as JSON
    $router->addRouteByParams('GET', '/info', [DemoController::class, 'info']);

    // Error handling demo routes
    $router->addRouteByParams('GET', '/error', [DemoController::class, 'handleError']);
    $router->addRouteByParams('GET', '/exception', [DemoController::class, 'handleException']);
    $router->addRouteByParams('GET', '/fatal', [DemoController::class, 'handleFatal']);
});

// Return configured application for index.php
return $app;
```

**What's happening here:**

1. **Service Provider Registration** (lines 14-18): RouteServiceProvider is new
   - Registers Router into container
   - Binds RouteCollection, RouteMatcher, RouteHandlerFactory
   - Makes routing components available for dependency injection

2. **Route Definition via Callback** (lines 21-32): Application::withRoutes() receives Router
   - Callback typed to receive Router instance
   - Router is autowired from container (injected by framework)
   - Routes defined declaratively, not via match() expressions

3. **Route Registration** (lines 25-31): Each route maps HTTP method + path to controller method
   - First parameter: HTTP method as string ('GET', 'POST', etc.)
   - Second parameter: URL path (no parameters yet, basic matching only)
   - Third parameter: Handler as `[Controller::class, 'method']` array
   - Fourth parameter (optional): Route name for URL generation

4. **No Manual Dispatching:** Routes stored in RouteCollection, dispatched by Router when request arrives

**File: `demo-app/public/index.php`**

```php
<?php

declare(strict_types=1);

// Test PHP 8.5 first-class callable in const expression

/**
 * @var \Larafony\Framework\Web\Application $app
 */
// Load configured application from bootstrap
// Application has routes registered, service providers booted
$app = require_once __DIR__ . '/../bootstrap/web_app.php';

// Run application
// This method:
// 1. Gets ServerRequest from container (PSR-7)
// 2. Gets Kernel from container
// 3. Calls Kernel->handle($request) which:
//    a. Router finds matching route
//    b. Route handler processes request
//    c. Kernel handles headers
//    d. Kernel handles redirects
// 4. Emits response to browser
$app->run();
```

**What's happening here:**

1. **Bootstrap Loading** (line 11): Get configured Application with registered routes
   - All routes already registered in bootstrap
   - All service providers already booted
   - Container has all dependencies ready

2. **Application::run()** (line 24): Single method replaces all previous manual logic
   - Previously (Chapter 5): Manual request creation, path matching, controller resolution, response emission
   - Now (Chapter 6): Single `run()` call handles entire request/response cycle

3. **Internal Flow of run():**
   ```php
   // Inside Application::run()
   $request = $this->get(ServerRequestInterface::class); // PSR-7 request
   $kernel = $this->get(Kernel::class);                  // HTTP kernel
   $response = $kernel->handle($request);                 // Process request
   $this->emit($response);                                // Send to browser
   ```

4. **Kernel Pipeline:**
   ```php
   // Inside Kernel::handle()
   return $this->router->handle($request)          // Find and execute route
       |> $this->handleHeaders(...)                 // Set HTTP headers
       |> (fn($r) => $this->handleRedirects($r));  // Handle Location header
   ```

**Comparison: Chapter 5 vs Chapter 6**

**Chapter 5 index.php (Manual Routing):**
```php
$app = require __DIR__ . '/../bootstrap/web_app.php';
$app->withServiceProviders([...]);

$request = $app->get(ServerRequestInterface::class);
$path = $request->getUri()->getPath();
$controller = $app->get(DemoController::class);

$response = match ($path) {
    '/' => $controller->home($request),
    '/info' => $controller->info($request),
    // ... more routes
};

$app->emit($response);
```

**Chapter 6 index.php (Router-Based):**
```php
$app = require __DIR__ . '/../bootstrap/web_app.php';
$app->run();
```

**Benefits:**
- **6 lines → 2 lines:** Massive reduction in boilerplate
- **Declarative:** Routes defined in one place (bootstrap)
- **Type-Safe:** Router validates handler signatures
- **Extensible:** Easy to add middleware, route parameters, etc. later
- **PSR-15 Compliant:** All handlers follow standard interface

### Running the Demo

```bash
cd framework/demo-app
php8.5 -S localhost:8000 -t public
```

Then visit:
- `http://localhost:8000/` - Homepage via routing
- `http://localhost:8000/info` - JSON response via routing
- `http://localhost:8000/nonexistent` - 404 RouteNotFoundError

**Expected output for `/`:**

Same HTML as Chapter 5, but now routed through:
1. Application::run()
2. Kernel::handle()
3. Router::handle()
4. Route::handle()
5. ClassMethodRouteHandler
6. DemoController::home()

### Key Takeaways

- **Declarative Routing:** Routes defined in bootstrap with fluent API, not scattered in match() expressions

- **PSR-15 Throughout:** Router, Kernel, Route, and all handlers implement PSR-15 RequestHandlerInterface

- **Pipe Operator:** Kernel uses PHP 8.5 pipe operator for clean response processing pipeline

- **Type Safety:** HttpMethod enum prevents typos, handler factories ensure correct signatures

- **Container Integration:** Router autowired with dependencies, handlers get dependency injection

- **Single Responsibility:**
  - Bootstrap: Route registration
  - Router: Route matching and dispatching
  - Kernel: Response post-processing
  - Application: Orchestration

- **Framework Evolution:**
  - **Chapter 4:** Container with dependency injection
  - **Chapter 5:** PSR-7 HTTP layer
  - **Chapter 6:** Basic routing with PSR-15 handlers
  - **Future:** Route parameters, middleware, URL generation

- **Simplified Entry Point:** index.php reduced from 40+ lines to 2 lines: `$app->run()`

---

📚 **Learn More:** This implementation is explained in detail with step-by-step tutorials, tests, and best practices at [masterphp.eu](https://masterphp.eu)
