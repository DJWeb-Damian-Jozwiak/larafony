# Chapter 15: Advanced Routing & Middleware System

> Part of the Larafony Framework - A modern PHP 8.5 framework built from scratch
>
> 📚 Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)

## Overview

Chapter 15 elevates Larafony's routing system to production-grade with attribute-based route discovery, automatic model binding, middleware integration, and compiled routes for optimal performance. This implementation demonstrates how modern PHP 8.5 attributes can replace traditional route configuration files while maintaining type safety and discoverability.

The advanced routing system introduces a complete PSR-15 middleware stack, allowing fine-grained request/response manipulation at global, group, and route levels. Route compilation transforms dynamic route patterns into pre-compiled regex, eliminating runtime pattern matching overhead in production environments.

Unlike frameworks that bolt attributes onto file-based routing as an afterthought, Larafony's routing was designed from day one for attributes, resulting in cleaner code, better IDE support, and zero configuration files for route definition.

## Key Components

### Core Routing

- **Router** - Advanced router extending the basic router with model binding resolution, attribute-based route loading, and route grouping (src/Larafony/Routing/Advanced/Router.php:14)
- **Route** - Enhanced route class supporting model bindings, parameters, middleware decorators, and compiled route optimization (src/Larafony/Routing/Advanced/Route.php:14)
- **RouteMatcher** - Matches incoming requests to registered routes using compiled regex patterns for performance (src/Larafony/Routing/Advanced/RouteMatcher.php)
- **RouteGroup** - Organizes routes with shared prefixes and middleware stacks (src/Larafony/Routing/Advanced/RouteGroup.php)

### Attribute System

Route definition and metadata via PHP 8.5 attributes:
- **#[Route]** - Defines HTTP routes on controller methods with path and HTTP methods (src/Larafony/Routing/Advanced/Attributes/Route.php:10)
- **#[RouteGroup]** - Groups routes under a common prefix at class level (src/Larafony/Routing/Advanced/Attributes/RouteGroup.php)
- **#[RouteParam]** - Specifies parameter constraints and model binding configuration (src/Larafony/Routing/Advanced/Attributes/RouteParam.php)
- **#[Middleware]** - Applies middleware to routes or entire controllers (src/Larafony/Routing/Advanced/Attributes/Middleware.php)

### Model Binding

- **ModelBinder** - Automatically resolves route parameters into model instances using reflection and type hints (src/Larafony/Routing/Advanced/ModelBinder.php:11)
- **RouteBinding** - Value object defining how a route parameter maps to a model and its resolution method (src/Larafony/Routing/Advanced/RouteBinding.php)

### Route Discovery & Caching

Attribute scanning and performance optimization:
- **AttributeRouteScanner** - Scans directories for controller classes with route attributes (src/Larafony/Routing/Advanced/AttributeRouteScanner.php)
- **AttributeRouteLoader** - Loads and registers routes from scanned attributes (src/Larafony/Routing/Advanced/AttributeRouteLoader.php)
- **RouteCache** - Serializes compiled routes to eliminate attribute scanning in production (src/Larafony/Routing/Advanced/Cache/RouteCache.php)
- **RouteCompiler** - Compiles route patterns into optimized regex for fast matching (src/Larafony/Routing/Advanced/Compiled/RouteCompiler.php:9)

### Middleware System

- **MiddlewareStack** - PSR-15 compliant middleware pipeline with before/after hooks and router integration (src/Larafony/Web/Middleware/MiddlewareStack.php:15)
- **RouteMiddleware** - Decorator managing middleware applied at route level (src/Larafony/Routing/Advanced/Decorators/RouteMiddleware.php)

### URL Generation

- **UrlGenerator** - Generates URLs from named routes with parameter substitution, supports absolute URLs and query string generation (src/Larafony/Routing/Advanced/UrlGenerator.php)
- **Router::generate()** - Convenience method delegating to UrlGenerator for quick URL generation (src/Larafony/Routing/Advanced/Router.php)

## PSR Standards Implemented

- **PSR-15**: HTTP Server Request Handlers - Full middleware compliance with `MiddlewareInterface` and `RequestHandlerInterface`. The MiddlewareStack implements proper delegation pattern and supports both global and route-specific middleware.

## New Attributes

- `#[Route(path: '/users/<id>', methods: ['GET', 'POST'])]` - Declares HTTP routes on controller methods with dynamic parameters
- `#[RouteGroup(prefix: '/api/v1')]` - Groups routes under a common URL prefix at class level
- `#[RouteParam(name: 'id', pattern: '\d+', bind: User::class)]` - Configures route parameters with validation patterns and model binding
- `#[Middleware(AuthMiddleware::class)]` - Applies middleware to routes or controllers, supports arrays for multiple middleware

## Usage Examples

### Basic Attribute Routing

```php
<?php

namespace App\Controllers;

use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Routing\Advanced\Attributes\Route;
use Larafony\Framework\Routing\Advanced\Attributes\RouteGroup;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[RouteGroup(prefix: '/api')]
class UserController
{
    public function __construct(
        private readonly ResponseFactory $responseFactory
    ) {}

    #[Route(path: '/users', methods: ['GET'])]
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        // GET /api/users
        $users = User::all();
        return $this->responseFactory->createJsonResponse($users);
    }

    #[Route(path: '/users/<id:\d+>', methods: ['GET'])]
    public function show(ServerRequestInterface $request): ResponseInterface
    {
        // GET /api/users/123
        $params = $request->getAttribute('routeParams');
        $user = User::find($params['id']);
        return $this->responseFactory->createJsonResponse($user);
    }

    #[Route(path: '/users', methods: ['POST'])]
    public function store(ServerRequestInterface $request): ResponseInterface
    {
        // POST /api/users
        $data = $request->getParsedBody();
        $user = User::create($data);
        return $this->responseFactory->createJsonResponse($user, 201);
    }
}
```

### Model Binding

```php
<?php

use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Routing\Advanced\Attributes\Route;
use Larafony\Framework\Routing\Advanced\Attributes\RouteParam;
use Larafony\Framework\Database\ORM\Model;
use App\Models\User;

class User extends Model
{
    public static function findForRoute(int|string $id): ?static
    {
        return static::find($id);
    }
}

class UserController
{
    public function __construct(
        private readonly ResponseFactory $responseFactory
    ) {}

    #[Route(path: '/users/<user>', methods: ['GET'])]
    #[RouteParam(name: 'user', pattern: '\d+', bind: User::class)]
    public function show(ServerRequestInterface $request, User $user): ResponseInterface
    {
        // $user is automatically resolved from route parameter via model binding
        // No need to manually fetch from routeParams - it's injected as a method parameter!

        return $this->responseFactory->createJsonResponse([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }
}

// Custom resolution method (e.g., find by slug instead of ID)
class User extends Model
{
    public static function findBySlug(string $slug): ?static
    {
        return static::where('slug', $slug)->first();
    }
}

class BlogController
{
    #[Route(path: '/posts/<slug:[a-z0-9-]+>', methods: ['GET'])]
    #[RouteParam(name: 'slug', bind: User::class, findMethod: 'findBySlug')]
    public function show(ServerRequestInterface $request, User $user): ResponseInterface
    {
        // $user resolved via findBySlug() instead of default findForRoute()
        // Pattern validation ([a-z0-9-]+) and binding in the same attribute!
        return $this->responseFactory->createJsonResponse($user);
    }
}
```

### Middleware Integration

```php
<?php

use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Http\JsonResponse;
use Larafony\Framework\Routing\Advanced\Attributes\Route;
use Larafony\Framework\Routing\Advanced\Attributes\Middleware;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

// Define middleware
class AuthMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $token = $request->getHeaderLine('Authorization');

        if (!$this->validateToken($token)) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        return $handler->handle($request);
    }
}

// Apply middleware at class level
#[RouteGroup(prefix: '/admin')]
#[Middleware(AuthMiddleware::class)]
class AdminController
{
    public function __construct(
        private readonly ResponseFactory $responseFactory
    ) {}

    #[Route(path: '/dashboard', methods: ['GET'])]
    public function dashboard(ServerRequestInterface $request): ResponseInterface
    {
        // AuthMiddleware runs before this method
        return $this->responseFactory->createResponse(200)
            ->withBody($streamFactory->createStream('Admin Dashboard'));
    }

    #[Route(path: '/users', methods: ['GET'])]
    #[Middleware(RateLimitMiddleware::class)] // Additional middleware for this route
    public function users(ServerRequestInterface $request): ResponseInterface
    {
        // Both AuthMiddleware and RateLimitMiddleware run
        return $this->responseFactory->createJsonResponse(User::all());
    }
}
```

### Route Caching & Compilation

```php
<?php

// In production, compile routes once for optimal performance
use Larafony\Framework\Routing\Advanced\Router;
use Larafony\Framework\Routing\Advanced\Cache\RouteCache;

$router = app(Router::class);

// Development: Load routes from attributes (scans files)
$router->loadAttributeRoutes(app_path('Controllers'));

// Production: Use cached routes (no file scanning)
if (config('app.cache_routes')) {
    $cache = new RouteCache(storage_path('routes/compiled.php'));

    if ($cache->isFresh()) {
        $router->loadCachedRoutes($cache->load());
    } else {
        $router->loadAttributeRoutes(app_path('Controllers'));
        $cache->save($router->getRoutes());
    }
}

// Routes are now compiled with pre-built regex patterns
// Matching is 10-100x faster than runtime compilation
```

**Console Commands for Route Caching:**

```bash
# Cache routes for production
php bin/larafony route:cache

# Clear route cache
php bin/larafony route:clear

# List all registered routes
php bin/larafony route:list
```

**RouteCache API:**

```php
<?php

use Larafony\Framework\Routing\Advanced\Cache\RouteCache;

$cache = new RouteCache(storage_path('cache/routes'));

// Check if cache exists and is valid
if ($cache->exists()) {
    $routes = $cache->load();
}

// Check if cache is fresh (not stale)
if ($cache->isFresh()) {
    // Use cached routes
}

// Save compiled routes
$cache->save($router->routes->all());

// Clear cache
$cache->clear();

// Get cache file path
$path = $cache->getPath(); // storage/cache/routes/compiled.php
```

### URL Generation

Generate URLs from named routes using the `UrlGenerator` or `Router::generate()` method:

```php
<?php

use Larafony\Framework\Routing\Advanced\UrlGenerator;
use Larafony\Framework\Routing\Advanced\Router;
use Larafony\Framework\Routing\Advanced\Attributes\Route;

// Define named routes
class UserController
{
    #[Route(path: '/users', methods: ['GET'], name: 'users.index')]
    public function index(): ResponseInterface { /* ... */ }

    #[Route(path: '/users/<id:\d+>', methods: ['GET'], name: 'users.show')]
    public function show(int $id): ResponseInterface { /* ... */ }

    #[Route(path: '/users/<id:\d+>/posts/<postId:\d+>', methods: ['GET'], name: 'users.posts.show')]
    public function showPost(int $id, int $postId): ResponseInterface { /* ... */ }
}

// Generate URLs via UrlGenerator (inject via DI)
class EmailService
{
    public function __construct(
        private readonly UrlGenerator $urlGenerator,
    ) {}

    public function sendWelcomeEmail(User $user): void
    {
        // Basic URL generation
        $profileUrl = $this->urlGenerator->route('users.show', ['id' => $user->id]);
        // Result: /users/123

        // Absolute URL (includes base URL from config)
        $absoluteUrl = $this->urlGenerator->route('users.show', ['id' => $user->id], absolute: true);
        // Result: https://example.com/users/123

        // Or use the shorthand method
        $absoluteUrl = $this->urlGenerator->routeAbsolute('users.show', ['id' => $user->id]);

        // Multiple parameters
        $postUrl = $this->urlGenerator->route('users.posts.show', [
            'id' => $user->id,
            'postId' => 42,
        ]);
        // Result: /users/123/posts/42

        // Extra parameters become query string
        $filteredUrl = $this->urlGenerator->route('users.index', [
            'page' => 2,
            'sort' => 'name',
        ]);
        // Result: /users?page=2&sort=name
    }
}

// Alternative: Use Router::generate() directly
class NotificationController
{
    public function __construct(
        private readonly Router $router,
    ) {}

    #[Route(path: '/notify/<userId:\d+>', methods: ['POST'])]
    public function notify(int $userId): ResponseInterface
    {
        $user = User::find($userId);

        // Generate URL via router
        $dashboardUrl = $this->router->generate('dashboard', absolute: true);

        // Send notification with link...

        return new JsonResponse(['sent' => true]);
    }
}
```

**Configuration:**

The base URL for absolute URLs is read from `config/app.php`:

```php
<?php
// config/app.php

return [
    'url' => env('APP_URL', 'https://example.com'),
    // ...
];
```

### Advanced: Programmatic Route Groups

```php
<?php

use Larafony\Framework\Routing\Advanced\Router;
use Larafony\Framework\Routing\Advanced\Route;

$router = app(Router::class);

// Create route groups programmatically
$router->group('/api/v1', function ($group) {
    // All routes in this group have /api/v1 prefix

    $group->addRoute(new Route(
        path: '/users',
        method: HttpMethod::GET,
        handlerDefinition: [UserController::class, 'index']
    ));

    $group->addRoute(new Route(
        path: '/users/<id:\d+>',
        method: HttpMethod::GET,
        handlerDefinition: [UserController::class, 'show']
    ));

}, [AuthMiddleware::class]); // Apply middleware to entire group
```

## Comparison with Other Frameworks

| Feature | Larafony | Laravel 12 | Symfony 7 |
|---------|----------|------------|-----------|
| Attribute Routing | ✅ Native, first-class | ⚠️ Via spatie/laravel-route-attributes package | ✅ Native, recommended approach |
| Route Definition | In controller methods | Separate routes/ files (or attributes via package) | In controllers or config files |
| Configuration | Zero config, pure attributes | routes/web.php, routes/api.php files | config/routes.yaml or attributes |
| Model Binding | #[RouteParam] attribute with explicit findMethod | Implicit via {param} naming or Route::bind() | ParamConverter annotation |
| Middleware | #[Middleware] attribute | $middleware property or route files | #[IsGranted] or security.yaml |
| Route Parameters | `<id:\d+>` inline patterns | `{id}` with separate where() | `{id<\d+>}` inline patterns |
| Named Routes | name parameter in attribute | ->name() method in route file | name parameter in attribute |
| Route Caching | Built-in RouteCache with serialization | php artisan route:cache command | cache:warmup command |
| Compiled Routes | Pre-compiled regex patterns | Runtime pattern compilation | Compiled to PHP files |
| PSR-15 Middleware | Native implementation | Laravel-specific middleware | Native PSR-15 support |
| Learning Curve | Low (self-documenting) | Medium (separate files) | Medium (many configuration options) |
| IDE Support | Excellent (attributes in code) | Good (route files separate) | Excellent (attributes in code) |

**Key Differences:**

- **Attribute-First Design**: Laravel 12 still primarily uses route files (`routes/web.php`) and requires a third-party package (spatie/laravel-route-attributes) for attribute-based routing. Larafony has native attribute support built into the routing core.

- **Zero Configuration**: Larafony routes are defined entirely in controller attributes—no route files, no configuration. Laravel requires route files or additional package installation. Symfony supports attributes but also uses YAML/XML config in many projects.

- **PSR-15 Compliance**: Larafony's middleware implements PSR-15 `MiddlewareInterface` and `RequestHandlerInterface`, ensuring compatibility with any PSR-15 middleware. Laravel uses its own middleware contract, limiting interoperability.

- **Inline Parameter Patterns**: Like Symfony, Larafony uses inline regex patterns (`<id:\d+>`) that keep route definitions concise. Laravel requires separate `where()` method calls to add constraints.

- **Compiled Route Performance**: Larafony pre-compiles routes into optimized regex patterns cached as PHP files. Laravel caches routes but compiles patterns at runtime. Symfony also compiles to PHP for maximum performance.

- **Model Binding Approaches**: Larafony uses explicit `#[RouteParam]` attributes declaring the binding and custom resolution method in one place. Laravel uses implicit binding via naming convention (`{user}` → `User`) which works great for simple cases, but requires `Route::bind()` in RouteServiceProvider or `getRouteKeyName()` in models for custom columns—configuration scattered across multiple files. Larafony's approach: everything in the attribute (route, pattern, binding, method). Laravel's approach: less boilerplate for standard cases, more magic.

- **Middleware Flexibility**: Larafony supports middleware at three levels (global, group, route) with both before and after hooks. Laravel has similar support but through different configuration mechanisms. Symfony relies more heavily on event listeners.

- **Educational Value**: Building advanced routing from scratch demonstrates how attributes work under the hood, how regex compilation optimizes performance, and how PSR-15 middleware creates a request/response pipeline—concepts that apply to any PHP framework.

## Real World Integration

This chapter's features are demonstrated in the demo application with middleware configuration and routing setup.

### Demo Application Changes

The demo application was updated to include middleware configuration, showcasing how to set up the global middleware stack that integrates with the advanced routing system.

### File Structure
```
demo-app/
├── config/
│   ├── database.php          # Database configuration for ORM model binding
│   └── middleware.php         # Middleware stack configuration
├── bootstrap/
│   └── console_app.php        # Updated to register database service provider
├── .env & .env.example        # Added database credentials
```

### Implementation Example

**File: `demo-app/config/middleware.php`**

```php
<?php

declare(strict_types=1);

use Larafony\Framework\Routing\Middleware\RouterMiddleware;

return [
    // Middleware that runs before global middleware
    'before_global' => [],

    // Global middleware that runs on every request
    'global' => [
        RouterMiddleware::class, // Core router middleware that dispatches routes
    ],

    // Middleware that runs after global middleware
    'after_global' => [],
];
```

**What's happening here:**
1. **Middleware Configuration Array**: Returns a structured array with three middleware groups (`before_global`, `global`, `after_global`)
2. **RouterMiddleware**: The core routing middleware that integrates the advanced router into the PSR-15 middleware stack
3. **Execution Order**: Middleware in `before_global` runs first, then `global`, then `after_global`, creating a flexible pipeline
4. **Extension Point**: Developers can add custom middleware (auth, CORS, rate limiting) to any group without modifying framework code

**File: `demo-app/config/database.php`**

```php
<?php

declare(strict_types=1);

return [
    'default' => env('DB_CONNECTION', 'mysql'),

    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'larafony'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ],
    ],
];
```

**What's happening here:**
1. **Database Configuration**: Defines database connections for model binding feature
2. **Environment Variables**: Uses `env()` helper to pull credentials from `.env` file
3. **MySQL Connection**: Configures MySQL driver with host, port, database name, and authentication
4. **Model Binding Support**: This configuration enables the `ModelBinder` to resolve route parameters into ORM model instances

### Running the Demo

```bash
cd demo-app

# Ensure database is configured in .env
cp .env.example .env
# Edit .env and set DB_* variables

# The middleware stack is automatically loaded by the Kernel
# Routes with model binding will work out of the box

# Example: Create a controller with attribute routing
php console make:controller UserController
```

**Expected output:**
```
✓ Middleware stack loaded with RouterMiddleware
✓ Advanced router initialized
✓ Attribute routes can be discovered from controllers
✓ Model binding works automatically for type-hinted parameters
```

### Key Takeaways

- **Centralized Configuration**: All middleware is configured in one place (`config/middleware.php`), making the request pipeline transparent and easy to modify
- **Framework Integration**: The RouterMiddleware bridges the PSR-15 middleware stack with Larafony's advanced routing system
- **Zero Boilerplate**: No code changes needed in controllers—just add attributes and the system discovers routes automatically
- **Production Ready**: The middleware stack and routing system are designed for production use with caching and compilation support

---

📚 **Learn More:** This implementation is explained in detail with step-by-step tutorials, tests, and best practices at [masterphp.eu](https://masterphp.eu)
