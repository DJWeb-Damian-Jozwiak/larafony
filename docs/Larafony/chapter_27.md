# Chapter 27: DebugBar & Model Eager Loading

> Part of the Larafony Framework - A modern PHP 8.5 framework built from scratch
>
> 📚 Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)

## Overview

Chapter 27 introduces two critical development features: a **professional DebugBar** for real-time application insights and **N+1 query prevention** through model eager loading. The DebugBar provides comprehensive debugging information during development, while eager loading ensures production-grade performance by eliminating the notorious N+1 query problem.

The DebugBar is a non-intrusive toolbar injected into HTML responses, collecting data through event listeners without modifying application logic. It tracks database queries with execution time and backtrace, cache operations (hits/misses/writes/deletes) with hit ratio calculation, view rendering with template names and data, route matching with parameters and controller info, request/response details (method, URI, headers, status), application performance (execution time, memory usage, peak memory), and timeline visualization showing the complete request lifecycle.

Model eager loading solves the N+1 query problem by loading related models in bulk rather than one-by-one. Instead of executing 1 + N queries (one to fetch parent models, then one query per parent for its relations), eager loading executes just 2 queries (one for parents, one for all related models), dramatically reducing database load and improving response times.

**Key Performance Impact:**
- **Without Eager Loading:** 101 queries for 100 users with roles (1 + 100)
- **With Eager Loading:** 2 queries for 100 users with roles (1 + 1)
- **Reduction:** 98% fewer queries

## Key Components

### DebugBar System

- **DebugBar** - Central orchestrator managing data collectors with `addCollector()` for registration, `collect()` for gathering data from all collectors, `enable()/disable()` for toggling, and `isEnabled()` for status checking
- **DataCollectorContract** - Interface for collectors with `collect(): array` to gather data and `getName(): string` for identification
- **InjectDebugBar Middleware** - PSR-15 middleware injecting toolbar into HTML responses by checking Content-Type (must be text/html), verifying status code (only 2xx/3xx, not errors), rendering toolbar view, and inserting before `</body>` tag

### Data Collectors

Each collector implements DataCollectorContract and listens to framework events:

- **QueryCollector** - Listens to QueryExecuted events, tracks all SQL queries with execution time, bindings, connection name, and backtrace (filtered to exclude framework internals), calculates total query time and count
- **CacheCollector** - Listens to CacheHit, CacheMissed, KeyWritten, KeyForgotten events, tracks operations with timestamps, calculates hit ratio percentage, monitors total cache size (bytes written)
- **ViewCollector** - Listens to ViewRendered events, tracks rendered views with names and data, measures rendering time per view
- **RouteCollector** - Listens to RouteMatched events, captures route name, URI pattern, HTTP method, controller/action, and matched parameters
- **RequestCollector** - Captures request details including HTTP method, URI, headers, query parameters, and request body
- **PerformanceCollector** - Measures execution time from REQUEST_TIME_FLOAT, tracks memory usage (current, peak, delta), formats bytes to human-readable units (B, KB, MB, GB)
- **TimelineCollector** - Creates visual timeline by listening to ApplicationBooting, ApplicationBooted, RouteMatched, QueryExecuted, ViewRendering, ViewRendered events, tracks event start/end times, calculates durations in milliseconds, and sorts chronologically

### Model Eager Loading

- **ModelQueryBuilder** - Enhanced with `with(array $relations)` method for specifying relations to eager load, supports nested relations via dot notation (e.g., 'user.profile.avatar'), stores eager load configuration in `$eagerLoad` array
- **EagerRelationsLoader** - Orchestrates eager loading by iterating through configured relations, delegating to appropriate relation loader, and passing nested relation configuration
- **RelationLoaderContract** - Interface for relation loaders with `load(array $models, string $relationName, RelationContract $relation, array $nested): void`
- **BelongsToLoader** - Loads belongsTo relations by collecting foreign key values from parent models, executing single whereIn query to fetch all related models, indexing by primary key for O(1) lookup, and assigning to parent models
- **HasManyLoader** - Loads hasMany relations by collecting local key values, executing single whereIn query, grouping results by foreign key, and assigning arrays to parent models
- **BelongsToManyLoader** - Loads belongsToMany relations via pivot tables by collecting parent IDs, querying pivot table, fetching related models, and grouping by parent ID
- **HasManyThroughLoader** - Loads hasManyThrough relations by traversing intermediate models, executing optimized join query, and grouping results

## New Attributes

No new attributes were introduced in this chapter. The DebugBar uses the existing #[Listen] attribute from Chapter 26 for event-based data collection.

## Usage Examples

### DebugBar Integration

The DebugBar is automatically enabled in development environments and displays at the bottom of HTML pages:

```php
<?php

// config/app.php - DebugBar is registered via DebugBarServiceProvider
use Larafony\Framework\DebugBar\ServiceProviders\DebugBarServiceProvider;

return [
    'providers' => [
        // ... other providers
        DebugBarServiceProvider::class,
    ],
];

// config/debugbar.php - Configure DebugBar behavior
use Larafony\Framework\Config\Environment\EnvReader;
use Larafony\Framework\DebugBar\Collectors\CacheCollector;
use Larafony\Framework\DebugBar\Collectors\PerformanceCollector;
use Larafony\Framework\DebugBar\Collectors\QueryCollector;
use Larafony\Framework\DebugBar\Collectors\RequestCollector;
use Larafony\Framework\DebugBar\Collectors\RouteCollector;
use Larafony\Framework\DebugBar\Collectors\TimelineCollector;
use Larafony\Framework\DebugBar\Collectors\ViewCollector;

return [
    'enabled' => EnvReader::read('APP_DEBUG', false),

    'collectors' => [
        'queries' => QueryCollector::class,
        'cache' => CacheCollector::class,
        'views' => ViewCollector::class,
        'route' => RouteCollector::class,
        'request' => RequestCollector::class,
        'performance' => PerformanceCollector::class,
        'timeline' => TimelineCollector::class,
    ]
];

// The middleware is automatically registered in HTTP kernel
// No manual configuration needed!
```

**What You See:**

When you load any HTML page in development, the DebugBar appears at the bottom showing:

- **Queries Tab:** All executed queries with syntax-highlighted SQL, execution time, backtrace to source, and bindings
- **Cache Tab:** Cache operations with hit/miss ratio, total operations, and size metrics
- **Views Tab:** Rendered templates with data passed to each view
- **Route Tab:** Matched route details with parameters
- **Request Tab:** Full request information (method, URI, headers, body)
- **Performance Tab:** Execution time, memory usage, peak memory
- **Timeline Tab:** Visual waterfall chart of application lifecycle

### Basic Eager Loading

```php
<?php

use App\Models\User;

// ❌ N+1 Problem (101 queries for 100 users)
$users = User::query()->get(); // 1 query

foreach ($users as $user) {
    echo $user->role->name; // 100 queries (one per user)
}
// Total: 101 queries

// ✅ With Eager Loading (2 queries for 100 users)
$users = User::query()->with(['role'])->get(); // 2 queries (users + roles)

foreach ($users as $user) {
    echo $user->role->name; // No query - already loaded
}
// Total: 2 queries
```

**DebugBar Shows:**
- Without eager loading: 101 queries, ~150ms total time
- With eager loading: 2 queries, ~3ms total time
- **Performance improvement:** 50x faster

### Nested Eager Loading

```php
<?php

use App\Models\Post;

// Load posts with author and author's profile
$posts = Post::query()
    ->with(['author.profile'])
    ->get();

// 3 queries total:
// 1. SELECT * FROM posts
// 2. SELECT * FROM users WHERE id IN (...)
// 3. SELECT * FROM profiles WHERE user_id IN (...)

foreach ($posts as $post) {
    echo $post->author->profile->bio; // No queries - all loaded
}
```

**Nested Relation Syntax:**
- `'author'` - Load author relation
- `'author.profile'` - Load author AND author's profile
- `'author.profile.avatar'` - Load author, profile, and avatar (3 levels deep)

### Multiple Relations

```php
<?php

use App\Models\User;

// Load multiple relations at once
$users = User::query()
    ->with(['role', 'permissions', 'posts'])
    ->get();

// 4 queries total:
// 1. SELECT * FROM users
// 2. SELECT * FROM roles WHERE id IN (...)
// 3. SELECT * FROM permissions WHERE user_id IN (...)
// 4. SELECT * FROM posts WHERE author_id IN (...)

foreach ($users as $user) {
    echo $user->role->name;
    echo count($user->permissions);
    echo count($user->posts);
    // All data already loaded - no additional queries
}
```

### Complex Nested Loading

```php
<?php

use App\Models\Category;

// Deep nesting with multiple branches
$categories = Category::query()
    ->with([
        'posts.author.profile',      // Posts -> Authors -> Profiles
        'posts.comments.user',        // Posts -> Comments -> Users
        'posts.tags',                 // Posts -> Tags
    ])
    ->get();

// 7 queries total:
// 1. SELECT * FROM categories
// 2. SELECT * FROM posts WHERE category_id IN (...)
// 3. SELECT * FROM users WHERE id IN (...)  -- authors
// 4. SELECT * FROM profiles WHERE user_id IN (...)
// 5. SELECT * FROM comments WHERE post_id IN (...)
// 6. SELECT * FROM users WHERE id IN (...)  -- comment authors
// 7. SELECT * FROM tags JOIN post_tag WHERE post_id IN (...)

foreach ($categories as $category) {
    foreach ($category->posts as $post) {
        echo $post->author->profile->bio;
        foreach ($post->comments as $comment) {
            echo $comment->user->name;
        }
        foreach ($post->tags as $tag) {
            echo $tag->name;
        }
    }
}
// All data accessed without additional queries!
```

**DebugBar Timeline Shows:**
- Query 1: Categories (2ms)
- Query 2: Posts (3ms)
- Query 3: Authors (2ms)
- Query 4: Profiles (1ms)
- Query 5: Comments (4ms)
- Query 6: Comment Users (2ms)
- Query 7: Tags (3ms)
- **Total: 17ms for complete dataset**

### Conditional Eager Loading

```php
<?php

use App\Models\User;

// Load relations based on condition
$query = User::query();

if ($includeRole) {
    $query->with(['role']);
}

if ($includePosts) {
    $query->with(['posts.comments']);
}

$users = $query->get();
```

### DebugBar in API Development

```php
<?php

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use App\Models\Product;

class ProductController
{
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        // Load products with relations
        $products = Product::query()
            ->with(['category', 'reviews.user'])
            ->limit(20)
            ->get();

        // DebugBar shows:
        // - 3 queries (products, categories, reviews + users)
        // - 15ms total query time
        // - Route: GET /api/products
        // - Response: 200 OK

        return json_response([
            'data' => $products->toArray(),
        ]);
    }
}
```

**What DebugBar Reveals:**
- All database queries with timing
- Cache hits/misses for product data
- Route matching details
- Total request processing time
- Memory usage

### Custom Data Collector

You can create custom collectors for specific debugging needs:

```php
<?php

namespace App\DebugBar\Collectors;

use Larafony\Framework\DebugBar\Contracts\DataCollectorContract;
use Larafony\Framework\Events\Attributes\Listen;
use App\Events\EmailSent;

class EmailCollector implements DataCollectorContract
{
    private array $emails = [];

    #[Listen]
    public function onEmailSent(EmailSent $event): void
    {
        $this->emails[] = [
            'to' => $event->to,
            'subject' => $event->subject,
            'time' => microtime(true),
        ];
    }

    public function collect(): array
    {
        return [
            'emails' => $this->emails,
            'count' => count($this->emails),
        ];
    }

    public function getName(): string
    {
        return 'emails';
    }
}

// Register in DebugBarServiceProvider
$debugBar->addCollector('emails', new EmailCollector());
```

## Implementation Details

### DebugBar

**Location:** `src/Larafony/DebugBar/DebugBar.php`

**Purpose:** Central orchestrator managing all data collectors and coordinating data collection.

**Key Methods:**
- `addCollector(string $name, DataCollectorContract $collector): void` - Register a collector (src/Larafony/DebugBar/DebugBar.php:18)
- `collect(): array<string, mixed>` - Gather data from all collectors (src/Larafony/DebugBar/DebugBar.php:41)
- `enable(): void` - Enable DebugBar (src/Larafony/DebugBar/DebugBar.php:23)
- `disable(): void` - Disable DebugBar (src/Larafony/DebugBar/DebugBar.php:28)
- `isEnabled(): bool` - Check if DebugBar is enabled (src/Larafony/DebugBar/DebugBar.php:33)

**Data Structure:**
```php
$collectors = [
    'queries' => QueryCollector,
    'cache' => CacheCollector,
    'views' => ViewCollector,
    'route' => RouteCollector,
    'request' => RequestCollector,
    'performance' => PerformanceCollector,
    'timeline' => TimelineCollector,
];
```

**Usage:**
```php
$debugBar = new DebugBar();
$debugBar->addCollector('queries', new QueryCollector());
$debugBar->addCollector('cache', new CacheCollector());

// Later, collect all data
$data = $debugBar->collect();
// Returns:
[
    'queries' => ['queries' => [...], 'count' => 42, 'total_time' => 125.5],
    'cache' => ['hits' => 15, 'misses' => 3, 'hit_ratio' => 83.33],
    // ...
]
```

### InjectDebugBar Middleware

**Location:** `src/Larafony/DebugBar/Middleware/InjectDebugBar.php`

**Purpose:** PSR-15 middleware that injects DebugBar toolbar HTML into responses.

**Key Methods:**
- `process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface` - Process request and inject toolbar (src/Larafony/DebugBar/Middleware/InjectDebugBar.php:24)
- `shouldInject(ResponseInterface $response): bool` - Check if injection is appropriate (src/Larafony/DebugBar/Middleware/InjectDebugBar.php:40)
- `injectDebugBar(ResponseInterface $response): ResponseInterface` - Inject toolbar HTML (src/Larafony/DebugBar/Middleware/InjectDebugBar.php:55)

**Injection Logic:**
1. Check if DebugBar is enabled - if not, return original response
2. Check Content-Type - must contain 'text/html'
3. Check status code - must be < 400 (not error page)
4. Find `</body>` tag in response body
5. Render toolbar view with collected data
6. Insert toolbar HTML before `</body>`
7. Return modified response

**Safety Checks:**
- Only injects into HTML responses (not JSON, XML, etc.)
- Only injects into successful responses (not 404, 500, etc.)
- Only injects if `</body>` tag exists
- Gracefully handles missing conditions

### DebugBarServiceProvider

**Location:** `src/Larafony/DebugBar/ServiceProviders/DebugBarServiceProvider.php`

**Purpose:** Service provider responsible for bootstrapping DebugBar with configuration-driven collector registration.

**Key Methods:**
- `boot(ContainerContract $container): void` - Register DebugBar and collectors from config (src/Larafony/DebugBar/ServiceProviders/DebugBarServiceProvider.php:16)

**Bootstrap Algorithm:**
1. Check if DebugBar is enabled in config - **early return if disabled** (zero overhead in production)
2. Create DebugBar instance
3. Load collectors configuration from `config/debugbar.php`
4. Iterate through collector class names
5. Resolve each collector from container (supports DI)
6. Register collector with DebugBar
7. Store collector instances for event listener discovery
8. Enable DebugBar
9. Register DebugBar singleton in container
10. Discover and register event listeners using ListenerDiscovery

**Performance Optimization:**
The provider uses an **early return pattern** when DebugBar is disabled:

```php
if (!$config->get('debugbar.enabled', false)) {
    return; // Don't create any instances in production
}
```

This ensures **zero overhead** in production environments - no collectors are instantiated, no event listeners registered, and no memory allocated for debugging infrastructure.

**Configuration-Driven Design:**
Instead of hardcoding collectors, the provider reads from `config/debugbar.php`:

```php
$collectors = $config->get('debugbar.collectors', []);
foreach ($collectors as $name => $collectorClass) {
    $collector = $container->get($collectorClass);
    $debugBar->addCollector($name, $collector);
}
```

**Benefits:**
- ✅ Easy to add/remove collectors - just edit config file
- ✅ Custom collectors supported - add class to config
- ✅ Dependency injection - collectors resolved from container
- ✅ Clean separation - logic in provider, configuration in config

**Usage:**
No manual usage needed - automatically registered in `config/app.php` providers array.

### QueryCollector

**Location:** `src/Larafony/DebugBar/Collectors/QueryCollector.php`

**Purpose:** Collect and analyze all database queries executed during request.

**Key Methods:**
- `onQueryExecuted(QueryExecuted $event): void` - Event listener for query execution (src/Larafony/DebugBar/Collectors/QueryCollector.php:21)
- `collect(): array` - Return collected query data (src/Larafony/DebugBar/Collectors/QueryCollector.php:36)

**Collected Data:**
```php
[
    'queries' => [
        [
            'sql' => 'SELECT * FROM users WHERE id = ?',
            'rawSql' => 'SELECT * FROM users WHERE id = 123',
            'time' => 2.45,  // milliseconds
            'connection' => 'mysql',
            'backtrace' => [/* filtered stack trace */],
        ],
    ],
    'count' => 42,
    'total_time' => 125.5,  // milliseconds
]
```

**Backtrace Filtering:** Removes framework internal calls to show only application code in stack trace.

### CacheCollector

**Location:** `src/Larafony/DebugBar/Collectors/CacheCollector.php`

**Purpose:** Monitor cache operations and calculate performance metrics.

**Key Methods:**
- `onCacheHit(CacheHit $event): void` - Track cache hit (src/Larafony/DebugBar/Collectors/CacheCollector.php:28)
- `onCacheMissed(CacheMissed $event): void` - Track cache miss (src/Larafony/DebugBar/Collectors/CacheCollector.php:43)
- `onKeyWritten(KeyWritten $event): void` - Track cache write (src/Larafony/DebugBar/Collectors/CacheCollector.php:54)
- `onKeyForgotten(KeyForgotten $event): void` - Track cache delete (src/Larafony/DebugBar/Collectors/CacheCollector.php:68)
- `collect(): array` - Return cache statistics (src/Larafony/DebugBar/Collectors/CacheCollector.php:79)

**Collected Data:**
```php
[
    'operations' => [/* all cache operations with timestamps */],
    'hits' => 15,
    'misses' => 3,
    'writes' => 5,
    'deletes' => 2,
    'total' => 25,
    'total_size' => 2048,  // bytes
    'hit_ratio' => 83.33,   // percentage
]
```

**Hit Ratio Calculation:** `(hits / (hits + misses)) * 100`

### TimelineCollector

**Location:** `src/Larafony/DebugBar/Collectors/TimelineCollector.php`

**Purpose:** Create visual timeline of application lifecycle events.

**Key Methods:**
- `onApplicationBooting(ApplicationBooting $event): void` - Track application boot start (src/Larafony/DebugBar/Collectors/TimelineCollector.php:41)
- `onApplicationBooted(ApplicationBooted $event): void` - Track application boot end (src/Larafony/DebugBar/Collectors/TimelineCollector.php:49)
- `onRouteMatched(RouteMatched $event): void` - Track route matching (src/Larafony/DebugBar/Collectors/TimelineCollector.php:57)
- `onQueryExecuted(QueryExecuted $event): void` - Track database query (src/Larafony/DebugBar/Collectors/TimelineCollector.php:66)
- `onViewRendering(ViewRendering $event): void` - Track view render start (src/Larafony/DebugBar/Collectors/TimelineCollector.php:75)
- `onViewRendered(ViewRendered $event): void` - Track view render end (src/Larafony/DebugBar/Collectors/TimelineCollector.php:81)
- `collect(): array` - Return timeline data (src/Larafony/DebugBar/Collectors/TimelineCollector.php:102)

**Timeline Events:**
```php
[
    'events' => [
        [
            'label' => 'Application Bootstrap',
            'start' => 1699999999.123,
            'end' => 1699999999.145,
            'duration' => 22.0,  // ms
            'memory' => 2097152,  // bytes
            'type' => 'framework',
        ],
        [
            'label' => 'Query: SELECT * FROM users',
            'start' => 1699999999.150,
            'end' => 1699999999.153,
            'duration' => 3.0,
            'memory' => 2150000,
            'type' => 'database',
        ],
    ],
    'total_time' => 125.5,
    'start_time' => 1699999999.000,
]
```

**Event Types:**
- `framework` - Application lifecycle events
- `routing` - Route matching
- `database` - SQL queries
- `view` - Template rendering

### EagerRelationsLoader

**Location:** `src/Larafony/Database/ORM/EagerLoading/EagerRelationsLoader.php`

**Purpose:** Orchestrate eager loading of model relations to prevent N+1 queries.

**Key Methods:**
- `load(array $models, array $eagerLoad): void` - Load all configured relations (src/Larafony/Database/ORM/EagerLoading/EagerRelationsLoader.php:22)
- `loadRelation(array $models, string $relationName, array $nested): void` - Load single relation (src/Larafony/Database/ORM/EagerLoading/EagerRelationsLoader.php:36)
- `getLoaderForRelation(RelationContract $relation): RelationLoaderContract` - Get appropriate loader (src/Larafony/Database/ORM/EagerLoading/EagerRelationsLoader.php:54)

**Algorithm:**
1. For each configured relation:
   2. Get relation instance from first model
   3. Determine loader type (BelongsTo, HasMany, etc.)
   4. Delegate to specific loader
   5. Pass nested relations for recursive loading

**Example:**
```php
$loader = new EagerRelationsLoader();
$loader->load($users, [
    'role' => [],           // Load role, no nesting
    'posts' => ['author'],  // Load posts with nested author
]);
```

### HasManyLoader

**Location:** `src/Larafony/Database/ORM/EagerLoading/HasManyLoader.php`

**Purpose:** Load hasMany relations efficiently with single query.

**Key Methods:**
- `load(array $models, string $relationName, RelationContract $relation, array $nested): void` - Load relation (src/Larafony/Database/ORM/EagerLoading/HasManyLoader.php:13)

**Algorithm:**
1. Extract foreign_key, local_key, related class from relation via reflection
2. Collect local key values from all parent models
3. Execute single `whereIn(foreign_key, local_keys)` query
4. Support nested eager loading recursively
5. Group results by foreign key value
6. Assign grouped arrays to parent models

**Example:**
```php
// Given: 100 users, each with multiple posts
// Without eager loading: 1 + 100 queries
// With eager loading: 1 + 1 queries

$users = User::query()->with(['posts'])->get();

// 2 queries:
// SELECT * FROM users
// SELECT * FROM posts WHERE user_id IN (1,2,3,...,100)
```

### BelongsToLoader

**Location:** `src/Larafony/Database/ORM/EagerLoading/BelongsToLoader.php`

**Purpose:** Load belongsTo relations efficiently.

**Key Methods:**
- `load(array $models, string $relationName, RelationContract $relation, array $nested): void` - Load relation (src/Larafony/Database/ORM/EagerLoading/BelongsToLoader.php:13)

**Algorithm:**
1. Extract foreign_key, local_key, related class from relation
2. Collect foreign key values from parent models
3. Execute single `whereIn(local_key, foreign_keys)` query
4. Index results by local key (primary key)
5. Assign individual models to parents (not arrays)

**Example:**
```php
// Given: 100 posts, each with one author
// Without eager loading: 1 + 100 queries
// With eager loading: 1 + 1 queries

$posts = Post::query()->with(['author'])->get();

// 2 queries:
// SELECT * FROM posts
// SELECT * FROM users WHERE id IN (1,2,3,...,50)  -- unique author IDs
```

### ModelQueryBuilder Enhancement

**Location:** `src/Larafony/Database/ORM/QueryBuilders/ModelQueryBuilder.php`

**Purpose:** Add eager loading support to model queries.

**Key Methods:**
- `with(array $relations): static` - Configure relations to eager load (src/Larafony/Database/ORM/QueryBuilders/ModelQueryBuilder.php:167)
- `get(): array<Model>` - Execute query and eager load relations (enhanced)

**Nested Relation Parsing:**
```php
// Input: ['author.profile.avatar', 'comments.user']
// Parsed to:
[
    'author' => ['profile' => ['avatar' => []]],
    'comments' => ['user' => []],
]
```

**Usage:**
```php
$posts = Post::query()
    ->where('published', '=', true)
    ->with(['author.profile', 'comments.user', 'tags'])
    ->limit(20)
    ->get();
```

## Comparison with Other Frameworks

| Feature | Larafony | Laravel | Symfony |
|---------|----------|---------|---------|
| **DebugBar Type** | Custom built-in | Laravel Debugbar (package) | Symfony Profiler (built-in) |
| **Event Integration** | PSR-14 event listeners | Custom event system | Symfony EventDispatcher |
| **Data Collectors** | 7 core collectors | 15+ collectors | 20+ collectors |
| **Timeline View** | Built-in | Available | Built-in (detailed) |
| **Eager Loading Syntax** | `with(['relation'])` | `with('relation')` or `with(['relation'])` | Doctrine: `JOIN FETCH` |
| **Nested Relations** | `'author.profile.avatar'` | `'author.profile.avatar'` | Manual JOIN or separate queries |
| **Relation Types** | BelongsTo, HasMany, BelongsToMany, HasManyThrough | Same + morphTo, morphMany, etc. | Doctrine: OneToOne, OneToMany, ManyToMany |
| **N+1 Detection** | DebugBar query count | Laravel Telescope, Debugbar | Doctrine query logger |
| **Lazy Loading Control** | Manual via with() | `Model::preventLazyLoading()` | Doctrine lazy/eager config |
| **Performance Tracking** | Built-in collectors | Debugbar + Telescope | Profiler + Blackfire |
| **Memory Monitoring** | PerformanceCollector | Debugbar | Profiler |
| **Cache Monitoring** | CacheCollector with hit ratio | Debugbar cache tab | Profiler cache panel |
| **Query Analysis** | Backtrace + timing | Debugbar + Telescope | Profiler + explain |

**Key Differences:**

- **Built-in vs Package**: Larafony DebugBar is native to the framework. Laravel uses a third-party package. Symfony Profiler is built-in and more extensive.
- **Event-Driven**: Larafony DebugBar uses PSR-14 events for clean separation. Laravel Debugbar hooks into Laravel events. Symfony uses data collectors directly.
- **Simplicity**: Larafony provides essential debugging info without overwhelming detail. Symfony Profiler is extremely detailed. Laravel Debugbar is mid-range.
- **Eager Loading**: Larafony and Laravel have identical eager loading syntax. Symfony/Doctrine uses different approach (JOIN FETCH in DQL).
- **ORM Differences**: Larafony uses ActiveRecord pattern (similar to Laravel Eloquent). Symfony uses Doctrine (DataMapper pattern).
- **N+1 Prevention**: All frameworks support eager loading, but implementation differs. Larafony matches Laravel's developer-friendly approach.
- **Production Usage**: Larafony DebugBar is development-only (disabled in production). Laravel has Telescope for production monitoring. Symfony Profiler can run in production with careful configuration.

## Testing

The DebugBar and eager loading features are tested through integration tests:

### DebugBar Integration Tests

**Coverage:** Tests verify:
- Middleware injection into HTML responses
- Collector data gathering
- Event listener registration
- Response modification without corruption
- Conditional injection (only HTML, only 2xx/3xx)

### Eager Loading Tests

**Coverage:** Tests verify:
- N+1 query prevention
- Single query execution per relation
- Nested relation loading
- Multiple relation loading
- Relation data integrity
- Support for all relation types (BelongsTo, HasMany, BelongsToMany, HasManyThrough)

**Example Test:**
```php
// Without eager loading
$users = User::query()->get();
$this->assertQueryCount(101); // 1 + 100

// With eager loading
$users = User::query()->with(['role'])->get();
$this->assertQueryCount(2); // 1 + 1
```

---

📚 **Learn More:** This implementation is explained in detail with step-by-step tutorials, tests, and best practices at [masterphp.eu](https://masterphp.eu)
