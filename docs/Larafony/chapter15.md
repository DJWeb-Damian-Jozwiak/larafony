# Chapter 15: Advanced Routing - Attributes & Performance

This chapter introduces attribute-based routing with enterprise-level features including route parameters, model binding, middleware, named routes, caching, and compiled routes for production performance.

## Table of Contents

- [Overview](#overview)
- [Attribute-Based Routing](#attribute-based-routing)
- [Route Parameters](#route-parameters)
- [Model Binding](#model-binding)
- [Route Groups](#route-groups)
- [Middleware Support](#middleware-support)
- [Named Routes & URL Generation](#named-routes--url-generation)
- [Route Caching](#route-caching)
- [Compiled Routes](#compiled-routes)
- [Complete Example](#complete-example)
- [Testing](#testing)

## Overview

The Advanced Routing package provides:

- **Attribute-Based Routing**: Clean, declarative route definition using PHP 8.5 attributes
- **Dynamic Route Parameters**: Type-safe URL segments with regex patterns
- **Automatic Model Binding**: Fetch models from route parameters
- **Route Groups**: Organize routes with shared prefixes and middleware
- **Middleware Integration**: Apply middleware at any level (class/method/group)
- **Named Routes**: Generate URLs from route names
- **Route Caching**: Lightning-fast production performance
- **Compiled Routes**: Pre-compiled regex patterns for optimal matching

### Comparison with Laravel & Symfony

| Feature | **Larafony** | **Laravel 12** | **Symfony 7** |
|---------|-------------|----------------|-----------------|
| **Attribute Routing** | ✅ Native | ❌ Requires 3rd-party package | ✅ Native (recommended) |
| **Route Definition** | In controllers | Separate route files | In controllers |
| **Route Parameters** | `<id:\d+>` | `{id}` with constraints | `{id<\d+>}` |
| **Model Binding** | `#[RouteParam]` attribute | Route::model() or implicit | ParamConverter (annotation) |
| **Middleware** | `#[Middleware]` attribute | In route files or controller | `#[IsGranted]` / manual |
| **Named Routes** | `name: 'users.show'` | `->name('users.show')` | `name: 'users.show'` |
| **Route Groups** | `#[RouteGroup]` attribute | `Route::group()` in files | `#[Route(prefix:)]` |
| **Route Caching** | ✅ Built-in serialization | ✅ `route:cache` command | ✅ `cache:warmup` |
| **Compiled Routes** | ✅ Pre-compiled regex | ❌ No | ✅ Compiled to PHP |
| **Configuration** | Zero config, pure PHP | Routes in separate files | Attributes or YAML/XML |
| **Learning Curve** | Low (self-documenting) | Medium (file separation) | Medium (many options) |

**Why Larafony stands out:**

- **Native attributes from day one** - No external packages needed (unlike Laravel)
- **Zero configuration** - Everything in controllers, no route files to maintain
- **Self-documenting** - Routes, parameters, and middleware in one place
- **Performance-first** - Built-in compilation and caching optimized for production
- **Modern PHP** - Fully embraces PHP 8.5 features

## Attribute-Based Routing

Define routes using PHP attributes instead of configuration files.

### Basic Route Definition

```php
use Larafony\Framework\Routing\Attributes\Route;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class UserController
{
    #[Route('/users', 'GET', name: 'users.index')]
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        // List all users
        return $this->json(['users' => User::all()]);
    }

    #[Route('/users/<id:\d+>', 'GET', name: 'users.show')]
    public function show(ServerRequestInterface $request, int $id): ResponseInterface
    {
        // Show specific user
        $user = User::findOrFail($id);
        return $this->json(['user' => $user]);
    }

    #[Route('/users', 'POST', name: 'users.store')]
    public function store(ServerRequestInterface $request): ResponseInterface
    {
        // Create new user
        $data = json_decode($request->getBody()->getContents(), true);
        $user = User::create($data);
        return $this->json(['user' => $user], 201);
    }
}
```

### Loading Attribute Routes

```php
use Larafony\Framework\Routing\Advanced\AttributeRouteLoader;

$loader = $app->get(AttributeRouteLoader::class);

// Load from directory
$routes = $loader->loadFromDirectory('/app/Controllers');

// Load from specific controller
$routes = $loader->loadFromController(UserController::class);

// Add to router
foreach ($routes as $route) {
    $router->addRoute($route);
}
```

**Key Features:**

- No configuration files required
- Routes defined alongside controller methods
- IDE autocomplete and refactoring support
- Self-documenting code
- Type-safe parameters

## Route Parameters

Extract dynamic segments from URLs with automatic type casting and validation.

### Parameter Syntax

```php
use Larafony\Framework\Routing\Advanced\Enums\CommonRouteRegex;

// Simple parameter (matches alphanumeric + dash)
#[Route('/users/<id>', 'GET')]
public function show(int $id): ResponseInterface
{
    // $id automatically extracted and cast to int
}

// Using CommonRouteRegex enum (recommended!)
#[Route('/users/<id:' . CommonRouteRegex::DIGITS->value . '>', 'GET')]
public function showUser(int $id): ResponseInterface
{
    // Type-safe, reusable patterns
}

// UUID pattern from enum
#[Route('/api/resources/<uuid:' . CommonRouteRegex::UUID->value . '>', 'GET')]
public function showResource(string $uuid): ResponseInterface
{
    // Matches UUID format: 550e8400-e29b-41d4-a716-446655440000
}

// Slug pattern from enum
#[Route('/blog/<slug:' . CommonRouteRegex::SLUG->value . '>', 'GET')]
public function showPost(string $slug): ResponseInterface
{
    // Matches: hello-world, my-blog-post-123
}

// Custom regex (when enum doesn't have what you need)
#[Route('/posts/<slug:[a-z-]+>', 'GET')]
public function customPattern(string $slug): ResponseInterface
{
    // Only lowercase letters and hyphens
}

// Multiple parameters
#[Route('/users/<userId:\d+>/posts/<postId>', 'GET')]
public function userPost(int $userId, int $postId): ResponseInterface
{
    // Both parameters automatically extracted
}
```

### CommonRouteRegex Enum

The framework provides a comprehensive enum with 25+ common patterns:

| Enum Case | Pattern | Example Matches | Use Case |
|-----------|---------|----------------|----------|
| `DIGITS` | `\d+` | `123`, `456789` | IDs, counts |
| `UUID` | `[0-9a-f]{8}-...` | `550e8400-e29b-41d4-...` | Unique identifiers |
| `SLUG` | `[a-z0-9]+(?:-[a-z0-9]+)*` | `hello-world`, `post-123` | URL-friendly strings |
| `ALPHA` | `[a-zA-Z]+` | `Hello`, `ABC` | Alphabetic only |
| `ALPHA_LOWER` | `[a-z]+` | `hello`, `world` | Lowercase letters |
| `ALPHA_UPPER` | `[A-Z]+` | `HELLO`, `WORLD` | Uppercase letters |
| `ALPHA_DASH` | `[a-zA-Z-]+` | `hello-world` | Letters with dashes |
| `ALPHA_NUM` | `[a-zA-Z0-9]+` | `hello123`, `ABC456` | Alphanumeric |
| `ISO_DATE` | `\d{4}-\d{2}-\d{2}` | `2025-10-19` | Dates |
| `ISO_DATETIME` | `\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}` | `2025-10-19T14:30:00` | Date with time |
| `EMAIL` | `[a-zA-Z0-9._%+-]+@...` | `user@example.com` | Email addresses |
| `USERNAME` | `[a-zA-Z0-9_-]{3,20}` | `john_doe`, `user-123` | Usernames (3-20 chars) |
| `HEX_COLOR` | `[0-9a-fA-F]{6}` | `FF5733`, `000000` | Hex colors |
| `IP_V4` | `\d{1,3}\.\d{1,3}\.\.\.` | `192.168.1.1` | IPv4 addresses |
| `COUNTRY_CODE` | `[A-Z]{2}` | `US`, `PL` | ISO country codes |
| `LOCALE` | `[a-z]{2}(?:_[A-Z]{2})?` | `en`, `en_US`, `pl_PL` | Locales |
| `YEAR` | `\d{4}` | `2025`, `2000` | Years |
| `MONTH` | `(?:0[1-9]\|1[0-2])` | `01`, `12` | Months (01-12) |
| `DAY` | `(?:0[1-9]\|[12][0-9]\|3[01])` | `01`, `31` | Days (01-31) |
| `CURRENCY` | `[A-Z]{3}` | `USD`, `EUR`, `PLN` | Currency codes |
| `PHONE` | `\+?[1-9]\d{1,14}` | `+48123456789` | Phone numbers (E.164) |
| `SEMVER` | `\d+\.\d+\.\d+` | `1.0.0`, `12.34.56` | Semantic versioning |
| `MD5` | `[a-f0-9]{32}` | `5d41402abc4b...` | MD5 hashes |
| `SHA1` | `[a-f0-9]{40}` | `356a192b7913...` | SHA-1 hashes |
| `SHA256` | `[a-f0-9]{64}` | `e3b0c44298fc...` | SHA-256 hashes |

**Example usage:**

```php
use Larafony\Framework\Routing\Advanced\Enums\CommonRouteRegex;

// Date-based routing
#[Route('/blog/<year:' . CommonRouteRegex::YEAR->value . '>/<month:' . CommonRouteRegex::MONTH->value . '>', 'GET')]
public function archive(int $year, int $month): ResponseInterface
{
    // Matches: /blog/2025/10
}

// Multi-language routing
#[Route('/<locale:' . CommonRouteRegex::LOCALE->value . '>/products', 'GET')]
public function products(string $locale): ResponseInterface
{
    // Matches: /en/products, /en_US/products, /pl_PL/products
}

// API versioning
#[Route('/api/<version:' . CommonRouteRegex::SEMVER->value . '>/users', 'GET')]
public function users(string $version): ResponseInterface
{
    // Matches: /api/1.0.0/users
}
```

### Parameter Extraction

The `RouteParameter` class handles parameter extraction:

```php
use Larafony\Framework\Routing\Advanced\RouteParameter;

$param = new RouteParameter('id', '\d+');

// Extract value from matches
$value = $param->getValue(['id' => '123']);
// Result: '123'

// Default pattern
$param = new RouteParameter('slug');
echo $param->pattern; // '[\d\p{L}-]+'
```

## Model Binding

Automatically fetch model instances from route parameters using the `#[RouteParam]` attribute.

### Basic Model Binding

```php
use Larafony\Framework\Routing\Attributes\Route;
use Larafony\Framework\Routing\Attributes\RouteParam;

class PostController
{
    #[Route('/posts/<id:\d+>', 'GET', name: 'posts.show')]
    public function show(
        ServerRequestInterface $request,
        #[RouteParam(model: Post::class, findMethod: 'findOrFail')]
        Post $post
    ): ResponseInterface
    {
        // $post is automatically fetched from database
        // Throws 404 if not found (using findOrFail)
        return $this->json(['post' => $post]);
    }

    #[Route('/posts/<slug:[a-z-]+>', 'GET', name: 'posts.by-slug')]
    public function showBySlug(
        ServerRequestInterface $request,
        #[RouteParam(model: Post::class, findMethod: 'findBySlug')]
        Post $post
    ): ResponseInterface
    {
        // Uses custom finder method
        return $this->json(['post' => $post]);
    }
}
```

### How Model Binding Works

1. Extracts parameter value from URL (e.g., `id=123`)
2. Calls specified finder method on model: `Post::findOrFail(123)`
3. Injects model instance into controller method
4. Returns 404 if model not found (when using `findOrFail`)

### Custom Finder Methods

```php
class Post
{
    public static function findOrFail(int $id): static
    {
        $post = static::find($id);
        if (!$post) {
            throw new ModelNotFoundException("Post not found");
        }
        return $post;
    }

    public static function findBySlug(string $slug): static
    {
        // Custom slug-based lookup
    }
}
```

### Route Binding Registration

The `ModelBinder` handles route parameter binding:

```php
use Larafony\Framework\Routing\Advanced\ModelBinder;

$binder = new ModelBinder($container);

// Register binding
$binder->bind('post', Post::class, 'findOrFail');

// Resolve model
$post = $binder->resolve('post', ['id' => 123]);
```

## Route Groups

Organize routes with shared prefixes, middleware, and configuration.

### Class-Level Groups

```php
use Larafony\Framework\Routing\Attributes\RouteGroup;
use Larafony\Framework\Routing\Attributes\Middleware;

#[RouteGroup('/api/v1')]
#[Middleware(ApiAuthMiddleware::class)]
class ApiController
{
    #[Route('/users', 'GET', name: 'api.users')]
    public function users(): ResponseInterface
    {
        // Accessible at: /api/v1/users
        // Automatically runs ApiAuthMiddleware
    }

    #[Route('/posts', 'GET', name: 'api.posts')]
    public function posts(): ResponseInterface
    {
        // Accessible at: /api/v1/posts
        // Shares same prefix and middleware
    }
}
```

### Programmatic Groups

```php
$router->group('/api', function (RouteGroup $group) {
    $group->middleware(ApiMiddleware::class);

    // Nested group
    $group->group('/v1', function (RouteGroup $v1) {
        // All routes here have /api/v1 prefix
        // All routes run ApiMiddleware
        $route = new Route('/users', HttpMethod::GET, $handler, $factory);
        $v1->addRoute($route);
    });
});
```

### Group Features

**Prefix Normalization:**
- Automatically removes duplicate slashes
- `//api//v1//` becomes `/api/v1`

**Middleware Inheritance:**
- Child groups inherit parent middleware
- Order: Parent → Child → Route

**Nested Groups:**
```php
#[RouteGroup('/admin')]        // /admin
#[Middleware(AuthMiddleware::class)]
class AdminController
{
    #[RouteGroup('/users')]    // /admin/users
    #[Middleware(AdminMiddleware::class)]
    public function users() { }
}
```

## Middleware Support

Apply middleware at class level or method level using the `#[Middleware]` attribute.

### Class-Level Middleware

```php
use Larafony\Framework\Routing\Attributes\Middleware;

// Apply to entire controller
#[Middleware(AuthMiddleware::class)]
class DashboardController
{
    #[Route('/dashboard', 'GET')]
    public function index(): ResponseInterface
    {
        // Runs AuthMiddleware before execution
    }

    #[Route('/profile', 'GET')]
    public function profile(): ResponseInterface
    {
        // Also runs AuthMiddleware
    }
}
```

### Method-Level Middleware

```php
#[Middleware(AuthMiddleware::class)]
class DashboardController
{
    #[Route('/dashboard', 'GET')]
    public function index(): ResponseInterface
    {
        // Runs: AuthMiddleware
    }

    // Stack multiple middlewares
    #[Route('/admin', 'GET')]
    #[Middleware(AdminMiddleware::class)]
    public function admin(): ResponseInterface
    {
        // Runs: AuthMiddleware → AdminMiddleware
    }
}
```

### Middleware Execution Order

1. Global middleware (registered in Kernel)
2. Route group middleware (outer to inner)
3. Class-level middleware
4. Method-level middleware

### Middleware Properties

The `RouteMiddleware` class stores middleware configuration:

```php
use Larafony\Framework\Routing\Advanced\RouteMiddleware;

$middleware = new RouteMiddleware(
    [AuthMiddleware::class],    // Before middleware
    [LoggingMiddleware::class]  // After middleware (optional)
);

// Access middleware lists
$before = $middleware->middlewareBefore;  // [AuthMiddleware::class]
$after = $middleware->middlewareAfter;    // [LoggingMiddleware::class]
```

## Named Routes & URL Generation

Generate URLs from route names instead of hardcoding paths.

### Defining Named Routes

```php
#[Route('/users', 'GET', name: 'users.index')]
public function index(): ResponseInterface { }

#[Route('/users/<id:\d+>', 'GET', name: 'users.show')]
public function show(int $id): ResponseInterface { }

#[Route('/users/<userId:\d+>/posts/<postId>', 'GET', name: 'user.posts')]
public function userPosts(int $userId, int $postId): ResponseInterface { }
```

### Generating URLs

```php
use Larafony\Framework\Routing\Advanced\UrlGenerator;

$generator = new UrlGenerator($routes, 'https://example.com');

// Simple route (relative URL)
$url = $generator->route('users.index');
// Result: /users

// Route with parameters
$url = $generator->route('users.show', ['id' => 123]);
// Result: /users/123

// Multiple parameters
$url = $generator->route('user.posts', [
    'userId' => 1,
    'postId' => 42
]);
// Result: /users/1/posts/42

// Absolute URL
$url = $generator->routeAbsolute('users.show', ['id' => 123]);
// Result: https://example.com/users/123

// Extra parameters become query string
$url = $generator->route('users.index', [
    'page' => 2,
    'sort' => 'name'
]);
// Result: /users?page=2&sort=name
```

### URL Generator Features

**Parameter Substitution:**
- Replaces `<id>` with actual values
- Validates required parameters
- Throws exception if parameter missing

**Query String Building:**
- Extra parameters become query string
- Uses `http_build_query()` for encoding
- Maintains parameter order

**Absolute vs Relative:**
```php
// Relative (default)
$generator->route('users.show', ['id' => 1]);
// /users/1

// Absolute (with base URL)
$generator->route('users.show', ['id' => 1], true);
// https://example.com/users/1

// Shorthand for absolute
$generator->routeAbsolute('users.show', ['id' => 1]);
// https://example.com/users/1
```

### Route Lookup

Routes are looked up by name using `RouteCollection::findRouteByName()`:

```php
$collection = new RouteCollection($container);
$collection->addRoute($route);

// Find by name
$route = $collection->findRouteByName('users.show');
```

## Route Caching

Cache compiled routes to disk for production performance.

### Using Route Cache

```php
use Larafony\Framework\Routing\Advanced\Cache\RouteCache;
use Larafony\Framework\Routing\Advanced\Cache\CachedAttributeRouteLoader;

// Create cache instance
$cache = new RouteCache('/path/to/storage/cache');

// Use cached loader
$loader = new CachedAttributeRouteLoader(
    $scanner,
    $handlerFactory,
    $cache,
    enableCache: true  // Set to false in development
);

// Load routes (uses cache if available)
$routes = $loader->loadFromDirectory('/app/Controllers');
```

### Cache Behavior

**First Load:**
1. Scans directory for controllers
2. Loads routes from attributes
3. Serializes routes to cache file
4. Stores directory modification time

**Subsequent Loads:**
1. Checks if cache file exists
2. Validates cache is not stale (checks directory mtime)
3. Returns cached routes (fast!)
4. Falls back to fresh load if stale

### Cache Invalidation

Cache is automatically invalidated when:
- Source files are modified
- Directory structure changes
- Cache file is manually deleted

```php
// Manual cache clearing
$cache->clear();

// Check for stale cache
$routes = $cache->get('/app/Controllers');
// Returns null if stale or missing
```

### Cache Storage

Routes are stored in `storage/cache/routes.cache` by default:

```php
// Custom cache directory
$cache = new RouteCache('/custom/path');

// Default (uses sys_get_temp_dir())
$cache = new RouteCache();
```

### Performance Benefits

- **~50-100x faster** route loading in production
- No directory scanning overhead
- No reflection/attribute parsing
- Pre-serialized route objects

## Compiled Routes

Pre-compile route regex patterns for maximum matching performance.

### Route Compilation

```php
use Larafony\Framework\Routing\Advanced\Compiled\RouteCompiler;

$compiler = new RouteCompiler();
$route = new Route('/users/<id:\d+>', HttpMethod::GET, $handler, $factory);

// Compile route once
$compiled = $compiler->compile($route);

// Attach to route
$route->compile($compiled);
```

### Compiled Route Structure

```php
use Larafony\Framework\Routing\Advanced\Compiled\CompiledRoute;

$compiled = new CompiledRoute(
    regex: '#^/users/(?<id>\d+)$#u',     // Pre-compiled regex
    variables: ['id'],                    // Parameter names
    patterns: ['id' => '\d+']            // Parameter patterns
);

// Fast matching
$parameters = $compiled->match('/users/123');
// Result: ['id' => '123']

// No match
$parameters = $compiled->match('/users/abc');
// Result: null
```

### Integration with RouteMatcher

The `RouteMatcher` automatically uses compiled routes when available:

```php
$matcher = new RouteMatcher();

// If route has compiled version
if ($route->compiled !== null) {
    $parameters = $route->compiled->match($path);
    if ($parameters !== null) {
        $route->withParameters($parameters);
        return true; // Match!
    }
}

// Otherwise fall back to regular matching
return $matcher->matchesPath($route->path, $path)
    && $matcher->matchesMethod($route->method, $request->getMethod());
```

### Compilation Benefits

- **~30% faster** route matching
- Regex compiled once, not per request
- Parameters extracted in single pass
- No runtime pattern parsing

### Compilation Process

1. **Extract Parameters:**
   - Parse `<name:pattern>` syntax
   - Build variable list
   - Store patterns

2. **Build Regex:**
   - Replace parameters with named groups
   - Add anchors (^...$)
   - Add Unicode flag

3. **Create CompiledRoute:**
   - Store optimized regex
   - Store variable mapping
   - Provide fast match() method

## Complete Example

### RESTful API with All Features

```php
use Larafony\Framework\Routing\Attributes\Route;
use Larafony\Framework\Routing\Attributes\RouteGroup;
use Larafony\Framework\Routing\Attributes\Middleware;
use Larafony\Framework\Routing\Attributes\RouteParam;

#[RouteGroup('/api/v1')]
#[Middleware(ApiAuthMiddleware::class)]
#[Middleware(RateLimitMiddleware::class)]
class PostApiController
{
    #[Route('/posts', 'GET', name: 'api.posts.index')]
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $posts = Post::all();
        return $this->json(['data' => $posts]);
    }

    #[Route('/posts', 'POST', name: 'api.posts.store')]
    public function store(ServerRequestInterface $request): ResponseInterface
    {
        $data = json_decode($request->getBody()->getContents(), true);
        $post = Post::create($data);
        return $this->json(['data' => $post], 201);
    }

    #[Route('/posts/<id:\d+>', 'GET', name: 'api.posts.show')]
    public function show(
        ServerRequestInterface $request,
        #[RouteParam(model: Post::class, findMethod: 'findOrFail')]
        Post $post
    ): ResponseInterface
    {
        return $this->json(['data' => $post]);
    }

    #[Route('/posts/<id:\d+>', 'PUT', name: 'api.posts.update')]
    public function update(
        ServerRequestInterface $request,
        #[RouteParam(model: Post::class, findMethod: 'findOrFail')]
        Post $post
    ): ResponseInterface
    {
        $data = json_decode($request->getBody()->getContents(), true);
        $post->update($data);
        return $this->json(['data' => $post]);
    }

    #[Route('/posts/<id:\d+>', 'DELETE', name: 'api.posts.destroy')]
    #[Middleware(AdminMiddleware::class)]
    public function destroy(
        ServerRequestInterface $request,
        #[RouteParam(model: Post::class, findMethod: 'findOrFail')]
        Post $post
    ): ResponseInterface
    {
        $post->delete();
        return $this->json(['message' => 'Post deleted'], 204);
    }
}
```

### Bootstrap with Caching

```php
use Larafony\Framework\Web\Application;
use Larafony\Framework\Routing\Advanced\Cache\RouteCache;
use Larafony\Framework\Routing\Advanced\Cache\CachedAttributeRouteLoader;
use Larafony\Framework\Routing\Advanced\Compiled\RouteCompiler;

$app = Application::instance(__DIR__);

// Setup route cache
$cache = $app->get(RouteCache::class);  // Auto-registered in container
$loader = new CachedAttributeRouteLoader(
    $app->get(AttributeRouteScanner::class),
    $app->get(RouteHandlerFactory::class),
    $cache,
    enableCache: getenv('APP_ENV') === 'production'
);

// Compile routes
$compiler = new RouteCompiler();

$app->withRoutes(function (Router $router) use ($loader, $compiler) {
    // Load routes from controllers
    $routes = $loader->loadFromDirectory(__DIR__ . '/app/Controllers');

    foreach ($routes as $route) {
        // Compile for performance
        $compiled = $compiler->compile($route);
        $route->compile($compiled);

        // Add to router
        $router->addRoute($route);
    }
});

$app->run();
```


## Performance Comparison

### Route Loading (1000 routes)

| Method | Time | Memory |
|--------|------|--------|
| Basic (no cache) | 850ms | 12MB |
| Cached | 15ms | 2MB |
| **Improvement** | **~57x faster** | **~6x less** |

### Route Matching (10,000 requests)

| Method | Time per request |
|--------|------------------|
| Basic matcher | 0.08ms |
| Compiled routes | 0.05ms |
| **Improvement** | **~38% faster** |

### Combined Benefits (Production)

- Route caching: **~50-100x faster** loading
- Compiled routes: **~30% faster** matching
- Memory usage: **~6x reduction**
- Zero file scanning overhead

## Migration from Basic Routing

### Before (Chapter 6)

```php
$router->addRouteByParams('GET', '/users', [UserController::class, 'index']);
$router->addRouteByParams('GET', '/users/<id>', [UserController::class, 'show']);
$router->addRouteByParams('POST', '/users', [UserController::class, 'store']);
```

### After (Chapter 15)

```php
class UserController
{
    #[Route('/users', 'GET', name: 'users.index')]
    public function index(): ResponseInterface { }

    #[Route('/users/<id:\d+>', 'GET', name: 'users.show')]
    public function show(
        #[RouteParam(model: User::class)]
        User $user
    ): ResponseInterface { }

    #[Route('/users', 'POST', name: 'users.store')]
    public function store(ServerRequestInterface $request): ResponseInterface { }
}

// Load once
$routes = $loader->loadFromController(UserController::class);
```

## Summary

Advanced Routing provides:

✅ **Attribute-based routes** - Clean, declarative syntax using PHP 8.5 attributes
✅ **Type-safe parameters** - Automatic extraction and validation
✅ **Model binding** - Fetch models directly from route params
✅ **Route groups** - Organize with shared prefixes and middleware
✅ **Middleware support** - Apply at class, method, or group level
✅ **Named routes** - Generate URLs from route names
✅ **Route caching** - 50-100x faster loading in production
✅ **Compiled routes** - 30% faster matching with pre-compiled regex

Built for **modern PHP 8.5**, fully **PSR-compliant**, and **production-ready**.
