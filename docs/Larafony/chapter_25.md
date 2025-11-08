# Chapter 25: Cache Optimization & Authorization Integration

> Part of the Larafony Framework - A modern PHP 8.5 framework built from scratch
>
> 📚 Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)

## Overview

Chapter 25 introduces advanced cache optimization features and seamless integration with the authorization system. Building upon the PSR-6 cache implementation from Chapter 24, this chapter adds enterprise-grade performance optimizations including in-memory caching with LRU eviction, automatic compression for large values, and cache warming utilities for preloading frequently accessed data.

> **⚠️ PHP 8.5 Extension Compatibility Notice (Nov. 8, 2025)**
>
> As of November 8, 2025, there are **no official builds for Redis and Memcached extensions for PHP 8.5**.
>
> **Options:**
> - **Recommended:** Use **FileStorage** driver (works out of the box, no setup required)
> - **Advanced:** Want Redis/Memcached? After running `composer create-project`, execute `./build.sh` from your project root to compile extensions from source
>
> All code examples and tests work with FileStorage out of the box. Redis and Memcached examples are provided for future compatibility and for users who compile extensions manually.

The chapter also demonstrates real-world cache usage by integrating the cache system with the authorization layer (User and Role entities), significantly reducing database queries for permission checks through intelligent caching with automatic invalidation.

Key optimizations include multi-backend storage support (File, Redis, Memcached) with identical behavior guaranteed through comprehensive DataProvider-based tests, cache tag support for group invalidation, and production-ready features like memory leak prevention and graceful degradation.

## Key Components

### Cache Optimization Layer

- **BaseStorage** - Abstract base class implementing in-memory caching with LRU eviction (prevents memory leaks in long-running processes), automatic compression for values exceeding 10KB threshold, and consistent interface for all storage backends
- **FileStorage** - File-based storage with LRU metadata tracking, automatic eviction when item limit reached, and atomic file operations
- **RedisStorage** - Redis backend with atomic increment/decrement operations, pipeline support for batch operations (setMultiple, getMultiple, deleteMultiple), configurable eviction policies (LRU, LFU, etc.), and automatic compression
- **MemcachedStorage** - Memcached backend with automatic flush fallback when getAllKeys() fails, TTL-based expiration (Memcached auto-removes expired items), and distributed cache support

### Cache Warming & Management

- **CacheWarmer** - Preloading utility for frequently accessed data with fluent registration API, batch warming with configurable batch size, force overwrite option, error handling with detailed statistics, and support for tagged cache items
- **TaggedCache** - Group invalidation support using tag-based cache keys (MD5 hash of tags), reference tracking for all keys under each tag, flush() method to clear all items with specific tags, and PSR-6 compliant key naming (no `:` characters)
- **CacheWarmCommand** - Console command (`cache:warm`) with `--force` flag to overwrite existing cache, `--batch` option for batch size configuration, and integration with registered warmers
- **CacheClearCommand** - Console command (`cache:clear`) supporting tag-based clearing and full cache flush

### Authorization Cache Integration

- **User Entity** - Enhanced with `hasRole()` and `hasPermission()` methods using 1-hour cache TTL, automatic cache invalidation on role changes (addRole/removeRole), and `clearAuthCache()` for manual invalidation
- **Role Entity** - Enhanced with cached `hasPermission()` method, cascading cache invalidation (clearing all users with the role), and automatic invalidation on permission changes (addPermission/removePermission)

## PSR Standards Implemented

- **PSR-6**: Cache interface - Full implementation with CacheItemPool and CacheItem, tag support through TaggedCache, and key validation (no reserved characters: `{}()/\@:`, max 64 chars)
- **PSR-11**: Container interface - Used in StorageFactory for dependency injection and CacheServiceProvider for cache initialization

## New Attributes

No new attributes were introduced in this chapter. The implementation focuses on optimizing existing cache functionality and integrating it with the authorization system.

## Usage Examples

### Basic Cache Optimization

```php
<?php

use Larafony\Framework\Cache\Cache;

// In-memory cache automatically used for repeated requests
$cache = Cache::instance();

// First call: fetches from backend + stores in memory
$user = $cache->get('user.123');

// Second call: returns from in-memory cache (no backend hit)
$user = $cache->get('user.123');

// Large values automatically compressed
$bigData = str_repeat('data', 5000); // > 10KB
$cache->put('large.data', $bigData); // Automatically compressed

// Tagged cache for group invalidation
$cache->tags(['users', 'statistics'])
    ->put('users.count', 1500, 3600);

$cache->tags(['users', 'statistics'])
    ->put('active.users', 420, 3600);

// Flush all items with 'users' tag
$cache->tags(['users'])->flush();
```

### Cache Warming

```php
<?php

use Larafony\Framework\Cache\Cache;

// Register cache warmers (e.g., in bootstrap file)
$cache = Cache::instance();
$warmer = $cache->warmer();

$warmer
    ->register(
        key: 'statistics.total_users',
        callback: fn() => User::count(),
        ttl: 3600,
        tags: ['statistics', 'users']
    )
    ->register(
        key: 'config.active_features',
        callback: fn() => Config::get('features.enabled', []),
        ttl: 7200,
        tags: ['config']
    )
    ->register(
        key: 'top.products',
        callback: fn() => Product::orderBy('sales', 'desc')->limit(10)->get(),
        ttl: 1800,
        tags: ['products', 'statistics']
    );

// Warm all registered caches
$result = $warmer->warmAll();
// Result: ['total' => 3, 'warmed' => 3, 'skipped' => 0, 'failed' => 0]

// Warm in batches (for large numbers of warmers)
$result = $warmer->warmInBatches(batchSize: 10, force: false);
// Result: ['total' => 3, 'warmed' => 2, 'skipped' => 1, 'failed' => 0, 'batches' => 1]
```

### Cached Authorization

```php
<?php

use Larafony\Framework\Database\ORM\Entities\User;
use Larafony\Framework\Database\ORM\Entities\Role;
use Larafony\Framework\Database\ORM\Entities\Permission;

// First check: queries database + caches result for 1 hour
$user = User::find(123);
if ($user->hasRole('admin')) {
    // Granted
}

// Second check: returns from cache (no database query)
if ($user->hasRole('admin')) {
    // Granted - instant response from cache
}

// Permission check with automatic caching
if ($user->hasPermission('users.create')) {
    // Permission granted - caches all user permissions
}

// Automatic cache invalidation on role changes
$adminRole = Role::where('name', 'admin')->first();
$user->addRole($adminRole); // Automatically clears cache for this user

// Next permission check will refresh cache
if ($user->hasPermission('users.delete')) {
    // Fresh data from database + new cache
}

// Cascading invalidation: changing role permissions clears all users
$permission = Permission::where('name', 'posts.delete')->first();
$adminRole->addPermission($permission); // Clears cache for ALL users with admin role
```

### Configuration: Multi-Store Setup

**Larafony's Unique Feature:** Unlike Laravel (which requires complex workarounds for multiple cache stores of the same driver type), Larafony natively supports **multiple cache stores per driver with independent configuration**.

Each store can have its own host, port, database, and prefix - enabling sophisticated caching strategies out of the box.

**Configuration File:** `config/cache.php`

```php
<?php

use Larafony\Framework\Config\Environment\EnvReader;

return [
    'default' => EnvReader::read('CACHE_DRIVER', 'file'),

    'stores' => [
        // Primary application cache
        'file' => [
            'driver' => 'file',
            'path' => EnvReader::read('CACHE_FILE_PATH', 'storage/cache'),
        ],

        // Redis for API cache
        'redis' => [
            'driver' => 'redis',
            'host' => EnvReader::read('REDIS_HOST', '127.0.0.1'),
            'port' => (int) EnvReader::read('REDIS_PORT', '6379'),
            'database' => (int) EnvReader::read('REDIS_CACHE_DB', '1'),
            'password' => EnvReader::read('REDIS_PASSWORD', null),
            'prefix' => EnvReader::read('REDIS_PREFIX', 'larafony:cache:'),
        ],

        // Second Redis for sessions (different server + prefix)
        'redis_sessions' => [
            'driver' => 'redis',
            'host' => '192.168.1.100',
            'port' => 6379,
            'database' => 2,
            'prefix' => 'sessions:',
        ],

        // Third Redis for background jobs (different prefix)
        'redis_jobs' => [
            'driver' => 'redis',
            'host' => 'redis.production.local',
            'port' => 6379,
            'database' => 3,
            'prefix' => 'jobs:',
        ],

        // Memcached for distributed cache
        'memcached' => [
            'driver' => 'memcached',
            'host' => EnvReader::read('MEMCACHED_HOST', '127.0.0.1'),
            'port' => (int) EnvReader::read('MEMCACHED_PORT', '11211'),
            'prefix' => EnvReader::read('MEMCACHED_PREFIX', 'larafony:cache:'),
        ],

        // Second Memcached cluster (different servers + prefix)
        'memcached_global' => [
            'driver' => 'memcached',
            'host' => 'cache-cluster.example.com',
            'port' => 11211,
            'prefix' => 'global:',
        ],
    ],
];
```

**Using Multiple Stores:**

```php
<?php

use Larafony\Framework\Cache\Cache;

// Use default store (from 'default' config key)
$cache = Cache::instance();
$cache->put('user.1', $userData);

// Switch to redis_sessions store
$cache->store('redis_sessions')->put('session.abc', $sessionData);

// Switch to redis_jobs store
$cache->store('redis_jobs')->put('job.123', $jobData);

// Each store maintains its own prefix and connection
$apiCache = $cache->store('redis');           // prefix: 'larafony:cache:'
$sessionCache = $cache->store('redis_sessions'); // prefix: 'sessions:'
$jobCache = $cache->store('redis_jobs');         // prefix: 'jobs:'

// No key collision between stores
$apiCache->put('user.1', $apiData);        // Key: larafony:cache:user.1
$sessionCache->put('user.1', $sessionData); // Key: sessions:user.1
$jobCache->put('user.1', $jobData);         // Key: jobs:user.1
```

**Why This Matters:**

1. **Isolation** - Different parts of your application can use separate cache stores without key collisions
2. **Performance** - Critical caches can use dedicated Redis instances with optimized settings
3. **Security** - Sensitive data (sessions) can be stored on a separate server with stricter access controls
4. **Scalability** - Each store can scale independently based on usage patterns
5. **No Workarounds** - Native framework support, no custom service providers or facades needed

**Laravel Comparison:** In Laravel, using multiple Redis connections for cache requires:
- Custom cache driver registration
- Manual service provider configuration
- Facade modifications or helper function workarounds

**Larafony Approach:** Just add store configuration and call `->store('name')` - done! 🎉

### Advanced: Multi-Backend Storage

```php
<?php

use Larafony\Framework\Cache\Storage\RedisStorage;
use Larafony\Framework\Cache\Storage\MemcachedStorage;
use Larafony\Framework\Cache\Storage\FileStorage;

// Redis with custom configuration
$redis = new \Redis();
$redis->connect('127.0.0.1', 6379);
$redisStorage = new RedisStorage($redis, 'app:');

// Configure eviction policy
$redisStorage->withEvictionPolicy(RedisEvictionPolicy::ALLKEYS_LFU);
$redisStorage->maxCapacity(256 * 1024 * 1024); // 256MB

// Memcached with multiple servers
$memcached = new \Memcached();
$memcached->addServer('cache1.example.com', 11211);
$memcached->addServer('cache2.example.com', 11211);
$memcachedStorage = new MemcachedStorage($memcached);

// File storage with custom directory
$fileStorage = new FileStorage('/var/cache/app');
$fileStorage->maxCapacity(5000); // Maximum 5000 items

// Compression control
$redisStorage->withCompression(enabled: true)
    ->withCompressionThreshold(bytes: 5120); // Compress values > 5KB
```

## Implementation Details

### CacheWarmer

**Location:** `src/Larafony/Cache/CacheWarmer.php`

**Purpose:** Utility class for preloading frequently accessed data into cache, reducing cold cache performance impact after deployments or cache flushes.

**Key Methods:**
- `register(string $key, callable $callback, DateInterval|int|null $ttl, array $tags)` - Register a cache warmer with key, value generator callback, TTL, and optional tags for group invalidation
- `warm(string $key, callable $callback, ...)` - Execute a single cache warmer immediately, with exception handling and error logging
- `warmAll(bool $force = false)` - Warm all registered caches, optionally forcing refresh even if keys exist
- `warmInBatches(int $batchSize, bool $force)` - Warm caches in batches with 100μs sleep between batches to reduce system load
- `clear()` - Remove all registered warmers (useful for testing)
- `count()` - Get number of registered warmers

**Dependencies:** Requires `Cache` instance for actual caching operations

**Usage:**
```php
$warmer = new CacheWarmer(Cache::instance());

$warmer->register(
    'users.active.count',
    fn() => User::where('is_active', 1)->count(),
    3600,
    ['users', 'statistics']
);

// Returns: ['total' => 1, 'warmed' => 1, 'skipped' => 0, 'failed' => 0]
$stats = $warmer->warmAll(force: false);
```

### User

**Location:** `src/Larafony/Database/ORM/Entities/User.php`

**Purpose:** Enhanced User entity with intelligent caching for roles and permissions, reducing N+1 queries and database load for authorization checks.

**Key Methods:**
- `hasRole(string $roleName): bool` - Check if user has role, with 1-hour cache (src/Larafony/Database/ORM/Entities/User.php:124)
- `hasPermission(string $permissionName): bool` - Check if user has permission through any role, with 1-hour cache (src/Larafony/Database/ORM/Entities/User.php:137)
- `clearAuthCache(): void` - Manually invalidate cached roles and permissions (src/Larafony/Database/ORM/Entities/User.php:163)
- `addRole(Role $role): void` - Add role to user with automatic cache invalidation (src/Larafony/Database/ORM/Entities/User.php:101)
- `removeRole(Role $role): void` - Remove role from user with automatic cache invalidation (src/Larafony/Database/ORM/Entities/User.php:114)

**Cache Keys:**
- `user.{id}.roles` - Array of role names for the user
- `user.{id}.permissions` - Array of all permission names from all roles

**Dependencies:** Uses `Cache::instance()` singleton for caching operations

**Usage:**
```php
$user = User::find(42);

// First call: SELECT roles, permissions - stores in cache
if ($user->hasRole('admin')) {
    // Process admin logic
}

// Second call: Returns from cache - no database query
if ($user->hasPermission('users.create')) {
    // Process permission logic
}

// Modify roles: automatic cache invalidation
$editorRole = Role::where('name', 'editor')->first();
$user->addRole($editorRole); // Cache cleared automatically

// Next check will refresh cache from database
$user->hasRole('editor'); // Fresh query + new cache
```

### Role

**Location:** `src/Larafony/Database/ORM/Entities/Role.php`

**Purpose:** Enhanced Role entity with cached permission checks and cascading cache invalidation affecting all users with this role.

**Key Methods:**
- `hasPermission(string $permissionName): bool` - Check if role has permission, with 1-hour cache (src/Larafony/Database/ORM/Entities/Role.php:45)
- `addPermission(Permission $permission): void` - Add permission with automatic cache clearing (src/Larafony/Database/ORM/Entities/Role.php:64)
- `removePermission(Permission $permission): void` - Remove permission with automatic cache clearing (src/Larafony/Database/ORM/Entities/Role.php:80)
- `clearPermissionsCache(): void` - Clear role's cached permissions and all associated users' cache (cascading invalidation) (src/Larafony/Database/ORM/Entities/Role.php:95)

**Cache Keys:**
- `role.{id}.permissions` - Array of permission names for the role

**Cascading Invalidation:** When a role's permissions change, all users with that role have their auth cache cleared to ensure they get fresh permission data.

**Dependencies:** Uses `Cache::instance()` singleton and accesses related `users` collection

**Usage:**
```php
$adminRole = Role::where('name', 'admin')->first();

// First call: Queries permissions + caches
if ($adminRole->hasPermission('users.delete')) {
    // Permission exists
}

// Add new permission: cascading cache invalidation
$newPermission = Permission::where('name', 'posts.publish')->first();
$adminRole->addPermission($newPermission);
// Clears: role.{id}.permissions + user.{id}.roles + user.{id}.permissions for ALL admin users

// All admin users will get fresh permissions on next check
foreach ($adminRole->users as $user) {
    $user->hasPermission('posts.publish'); // Fresh from database
}
```

### FileStorage

**Location:** `src/Larafony/Cache/Storage/FileStorage.php`

**Purpose:** File-based cache storage with LRU (Least Recently Used) eviction, access log tracking in meta.json, and atomic file operations.

**Key Methods:**
- `getFromBackend(string $key): ?array` - Read cache file, update access log, and return unserialized data
- `setToBackend(string $key, array $data): bool` - Serialize and write data to file, update access log, evict LRU if needed
- `deleteFromBackend(string $key): bool` - Delete cache file and remove from access log (idempotent - returns true even if file doesn't exist)
- `clearBackend(): bool` - Delete all *.cache files and reset access log
- `maxCapacity(int $size): void` - Set maximum number of cached items, triggering eviction if exceeded

**Dependencies:** Extends `BaseStorage` for in-memory cache and compression features

**LRU Implementation:** Access times stored in `meta.json`, oldest entries evicted when capacity reached

**Usage:**
```php
$storage = new FileStorage('/var/cache/app');
$storage->maxCapacity(1000); // Limit to 1000 items

// Automatic LRU eviction when limit reached
for ($i = 0; $i < 1001; $i++) {
    $storage->set("key.$i", ['value' => "data$i", 'expiry' => time() + 3600]);
}
// Oldest item automatically removed to make room for new item
```

### RedisStorage

**Location:** `src/Larafony/Cache/Storage/RedisStorage.php`

**Purpose:** High-performance Redis-based storage with atomic operations, pipeline support for batch operations, and configurable eviction policies.

**Key Methods:**
- `getFromBackend(string $key): ?array` - Fetch from Redis with automatic decompression
- `setToBackend(string $key, array $data): bool` - Store in Redis with automatic compression and TTL handling (uses SETEX for expiring keys)
- `deleteFromBackend(string $key): bool` - Delete from Redis (idempotent - always returns true)
- `clearBackend(): bool` - Scan and delete all keys with prefix using SCAN iterator
- `setMultiple(array $items): bool` - Batch set using Redis PIPELINE for better performance
- `getMultiple(array $keys): array` - Batch get using MGET command
- `deleteMultiple(array $keys): bool` - Batch delete using DEL command
- `increment(string $key, int $value): int` - Atomic increment using INCRBY (race-condition safe)
- `decrement(string $key, int $value): int` - Atomic decrement using DECRBY (race-condition safe)
- `withEvictionPolicy(RedisEvictionPolicy $policy): void` - Configure eviction policy (LRU, LFU, etc.)
- `maxCapacity(int $size): void` - Set maximum memory in bytes

**Dependencies:** Requires `\Redis` extension and instance, extends `BaseStorage`

**Eviction Policies:** Supports ALLKEYS_LRU, ALLKEYS_LFU, VOLATILE_LRU, VOLATILE_LFU, VOLATILE_TTL, etc.

**Usage:**
```php
$redis = new \Redis();
$redis->connect('127.0.0.1', 6379);
$storage = new RedisStorage($redis, 'app:');

// Configure for high-traffic scenarios
$storage->withEvictionPolicy(RedisEvictionPolicy::ALLKEYS_LFU);
$storage->maxCapacity(512 * 1024 * 1024); // 512MB

// Atomic counter operations (safe for concurrent requests)
$storage->increment('page.views', 1);
$storage->increment('api.calls', 5);
$storage->decrement('available.slots', 1);

// Batch operations using pipeline
$items = [
    'product.1' => ['value' => ['name' => 'Widget'], 'expiry' => time() + 3600],
    'product.2' => ['value' => ['name' => 'Gadget'], 'expiry' => time() + 3600],
    'product.3' => ['value' => ['name' => 'Tool'], 'expiry' => time() + 3600],
];
$storage->setMultiple($items); // Single pipeline execution
```

### MemcachedStorage

**Location:** `src/Larafony/Cache/Storage/MemcachedStorage.php`

**Purpose:** Memcached-based distributed cache storage with automatic TTL handling and flush fallback for clear operations.

**Key Methods:**
- `getFromBackend(string $key): ?array` - Fetch from Memcached with error handling (distinguishes between cache miss and errors)
- `setToBackend(string $key, array $data): bool` - Store in Memcached with TTL calculation (Memcached auto-removes expired items)
- `deleteFromBackend(string $key): bool` - Delete from Memcached (idempotent - returns true even if key doesn't exist)
- `clearBackend(): bool` - Attempts getAllKeys() for prefix-based clearing, falls back to flush() if unavailable
- `maxCapacity(int $size): void` - Advisory only - actual memory limits set in memcached server configuration

**Dependencies:** Requires `\Memcached` extension and instance, extends `BaseStorage`

**Memcached Limitation:** getAllKeys() often returns empty array even when keys exist, so clearBackend() uses flush() which clears ALL data from Memcached instance

**TTL Behavior:** Unlike File/Redis which store expired data (filtered by CacheItemPool), Memcached automatically removes expired items

**Usage:**
```php
$memcached = new \Memcached();
$memcached->addServer('cache1.example.com', 11211);
$memcached->addServer('cache2.example.com', 11211); // Multi-server
$storage = new MemcachedStorage($memcached);

// Memcached automatically removes expired items
$storage->set('session.abc', [
    'value' => ['user_id' => 42],
    'expiry' => time() + 1800 // 30 minutes
]);

// After 30 minutes: Memcached automatically deleted the key
$data = $storage->get('session.abc'); // Returns null (not found)

// Clear all cache (uses flush - clears ENTIRE Memcached instance)
$storage->clear(); // All keys on all servers cleared
```

### TaggedCache

**Location:** `src/Larafony/Cache/TaggedCache.php`

**Purpose:** Implements tag-based cache invalidation allowing group clearing of related cache entries, using tag references and MD5 hashing for key generation.

**Key Methods:**
- `get(string $key, mixed $default): mixed` - Get value from tagged cache
- `put(string $key, mixed $value, DateInterval|int|null $ttl): bool` - Store value with tags, adding to tag references
- `forget(string $key): bool` - Remove tagged cache item
- `has(string $key): bool` - Check if tagged key exists
- `remember(string $key, DateInterval|int $ttl, callable $callback): mixed` - Get or set with callback
- `flush(): bool` - Clear all cache items associated with the tags, using tag reference tracking
- `getTagKeys(string $tag): array` - Get all keys associated with a specific tag

**Dependencies:** Wraps `Cache` instance, uses tag reference tracking with keys like `tag.{name}.keys`

**Tag Hashing:** Creates cache keys using MD5 hash of pipe-separated tags (e.g., `tagged.{md5('users|statistics')}.count`)

**PSR-6 Compliance:** Uses `.` separator instead of `:` to comply with PSR-6 reserved character restrictions

**Usage:**
```php
$cache = Cache::instance();

// Cache with multiple tags
$cache->tags(['users', 'statistics'])
    ->put('users.total', 1500, 3600);

$cache->tags(['users', 'active'])
    ->put('users.active', 420, 3600);

$cache->tags(['statistics', 'reports'])
    ->put('monthly.report', ['data' => '...'], 7200);

// Flush all items tagged with 'users' (clears users.total and users.active)
$cache->tags(['users'])->flush();

// monthly.report still exists (only tagged with 'statistics' and 'reports')
$report = $cache->tags(['statistics', 'reports'])->get('monthly.report');

// Get all keys for a tag
$userKeys = $cache->tags(['users'])->getTagKeys('users');
// Returns: ['tagged.{hash}.users.total', 'tagged.{hash}.users.active']
```

## Comparison with Other Frameworks

| Feature | Larafony | Laravel | Symfony |
|---------|----------|---------|---------|
| **PSR-6 Compliance** | Full implementation with CacheItemPool and CacheItem | Partial - provides facades and helpers, not strict PSR-6 | Full PSR-6 and PSR-16 implementation |
| **Cache Backends** | File, Redis, Memcached with unified interface | File, Redis, Memcached, Database, DynamoDB, Array | Many adapters: APCu, Redis, Memcached, PDO, Filesystem, Chain, etc. |
| **Tagged Cache** | PSR-6 compliant with `.` separator, MD5 hashing | Native support with `tags()` method, uses `:` separator | Native support in Symfony Cache component |
| **In-Memory Cache** | Built into BaseStorage with LRU eviction (1000 items) | Not built-in, relies on backend only | ArrayAdapter available for in-memory caching |
| **Compression** | Automatic for values > 10KB, configurable threshold | Not built-in, must implement manually | Not built-in by default |
| **Cache Warming** | Dedicated CacheWarmer class with batch support | `cache:warm` command for config/routes, custom warmers manual | `cache:warmup` command with cache warmers |
| **Atomic Operations** | RedisStorage: increment/decrement with INCRBY/DECRBY | Available through Redis facade | Available through Redis adapter |
| **Batch Operations** | RedisStorage: pipeline-based setMultiple/getMultiple | Not directly exposed in cache API | Available in certain adapters |
| **Authorization Cache** | Direct integration with User/Role entities, 1-hour TTL | Manual implementation needed | Manual implementation needed |
| **Eviction Policies** | RedisStorage: configurable (LRU, LFU, etc.) | Configured at Redis server level | Configured at Redis server level |
| **Configuration** | Attributes + Config, PSR-11 container | Array configuration files | YAML/PHP configuration |
| **Storage Abstraction** | StorageContract interface with BaseStorage template | CacheStore interface | Symfony Cache Adapter interface |

**Key Differences:**

- **PSR-First Design**: Larafony strictly adheres to PSR-6 specifications (e.g., no `:` in cache keys), while Laravel provides a developer-friendly API that doesn't strictly follow PSR-6
- **Built-in Optimizations**: Larafony includes in-memory caching with LRU eviction and automatic compression out of the box, while Laravel and Symfony require manual implementation or separate packages
- **Authorization Integration**: Larafony provides ready-to-use cached authorization on User/Role entities with automatic invalidation, reducing the boilerplate needed in Laravel/Symfony
- **Testing Approach**: Larafony uses PHPUnit DataProvider to ensure all storage backends (File, Redis, Memcached) behave identically with the same test suite (51 tests × 3 backends = 153 assertions)
- **Memory Safety**: BaseStorage includes automatic LRU eviction to prevent memory leaks in long-running processes (queue workers, daemons), which is not a default feature in Laravel or standard Symfony
- **Attribute-Based**: Larafony uses PHP 8.5 attributes for cache configuration where applicable, maintaining consistency with the framework's design philosophy

## Testing

The cache optimization features are covered by comprehensive test suites:

### CacheWarmerTest

**Location:** `tests/Larafony/Cache/CacheWarmerTest.php`

**Coverage:** 12 tests covering all CacheWarmer functionality
- Registration of single and multiple warmers
- Warming single keys with and without tags
- WarmAll with skip existing, force overwrite, and failure handling
- Batch warming with configurable batch size
- Clearing registered warmers
- Complex callbacks and TTL variations

**All tests pass:** ✅ 12/12 tests, multiple assertions

### StorageTest (DataProvider Pattern)

**Location:** `tests/Larafony/Cache/Storage/StorageTest.php`

**Coverage:** 17 test methods × 3 storage backends = 51 tests total
- **Basic Operations**: set/get, delete, clear, has
- **Edge Cases**: non-existent keys, expired items, no expiry
- **Data Types**: complex data structures, large data (20KB), empty values, zero, false, null
- **Special Cases**: special characters in keys, overwriting existing items, multiple operations

**DataProvider Pattern:** Single test suite runs against File, Redis, and Memcached to ensure identical behavior

**All tests pass:** ✅ 51/51 tests, 145 assertions

### CachedAuthorizationTest

**Location:** `tests/Larafony/Auth/CachedAuthorizationTest.php`

**Coverage:** 5 tests for authorization caching
- User roles caching (1-hour TTL)
- User permissions caching (aggregated from all roles)
- Role permissions caching
- Cache expiration behavior
- Multiple users with independent caches

**All tests pass:** ✅ 5/5 tests, 16 assertions

**Total Test Coverage:**
- 68 tests across 3 test suites
- 161+ assertions
- All 3 storage backends tested with identical test suite
- 100% pass rate

---

📚 **Learn More:** This implementation is explained in detail with step-by-step tutorials, tests, and best practices at [masterphp.eu](https://masterphp.eu)
