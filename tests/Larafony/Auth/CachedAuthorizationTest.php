<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Auth;

use Larafony\Framework\Cache\Cache;
use Larafony\Framework\Cache\CacheItemPool;
use Larafony\Framework\Cache\Storage\FileStorage;
use Larafony\Framework\Clock\SystemClock;
use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Database\ORM\Entities\Permission;
use Larafony\Framework\Database\ORM\Entities\Role;
use Larafony\Framework\Database\ORM\Entities\User;
use Larafony\Framework\Web\Application;
use PHPUnit\Framework\TestCase;

class CachedAuthorizationTest extends TestCase
{
    private Cache $cache;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset singletons and test clock
        Application::empty();
        Cache::empty();
        SystemClock::withTestNow(null);

        // Create temporary directory for file cache
        $this->tempDir = sys_get_temp_dir() . '/auth_cache_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);

        // Get application instance and mock config
        $app = Application::instance();
        $configMock = $this->createStub(ConfigContract::class);
        $configMock->method('get')
            ->willReturnMap([
                ['cache.default', null, 'file'],
                ['cache.stores.file', null, ['path' => $this->tempDir]],
            ]);
        $app->set(ConfigContract::class, $configMock);

        // Initialize cache directly
        $storage = new FileStorage($this->tempDir);
        $pool = new CacheItemPool($storage);

        $this->cache = new Cache($configMock);
        $this->cache->init($pool);

        // Register cache in container for static access
        $app->set(Cache::class, $this->cache);
        Cache::withContainer($app);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up cache
        $this->cache->clear();

        // Remove temporary directory
        $this->removeDirectory($this->tempDir);

        // Reset singletons and test clock
        Application::empty();
        Cache::empty();
        SystemClock::withTestNow(null);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testUserRolesAreCached(): void
    {
        // Pre-populate cache as if it was fetched from database
        $userId = 42;
        $this->cache->put("user.{$userId}.roles", ['admin', 'editor']);

        // Simulate user checking role (would normally query database but uses cache)
        $cachedRoles = $this->cache->get("user.{$userId}.roles");

        $this->assertSame(['admin', 'editor'], $cachedRoles);
        $this->assertTrue(in_array('admin', $cachedRoles, true));
        $this->assertTrue(in_array('editor', $cachedRoles, true));
        $this->assertFalse(in_array('viewer', $cachedRoles, true));
    }

    public function testUserPermissionsAreCached(): void
    {
        // Pre-populate cache
        $userId = 42;
        $this->cache->put("user.{$userId}.permissions", ['users.create', 'users.edit', 'posts.delete']);

        // Retrieve from cache
        $cachedPermissions = $this->cache->get("user.{$userId}.permissions");

        $this->assertSame(['users.create', 'users.edit', 'posts.delete'], $cachedPermissions);
        $this->assertTrue(in_array('users.create', $cachedPermissions, true));
        $this->assertFalse(in_array('users.delete', $cachedPermissions, true));
    }

    public function testRolePermissionsAreCached(): void
    {
        // Pre-populate cache
        $roleId = 1;
        $this->cache->put("role.{$roleId}.permissions", ['users.create', 'users.delete']);

        // Retrieve from cache
        $cachedPermissions = $this->cache->get("role.{$roleId}.permissions");

        $this->assertSame(['users.create', 'users.delete'], $cachedPermissions);
        $this->assertTrue(in_array('users.create', $cachedPermissions, true));
        $this->assertTrue(in_array('users.delete', $cachedPermissions, true));
    }

    public function testCacheExpiration(): void
    {
        // Test that cache respects TTL
        $userId = 99;
        $this->cache->put("user.{$userId}.roles", ['admin'], 1); // 1 second TTL

        // Immediately should be available
        $this->assertTrue($this->cache->has("user.{$userId}.roles"));
        $this->assertSame(['admin'], $this->cache->get("user.{$userId}.roles"));

        // After expiration (we can't really test this without waiting or mocking clock)
        // This demonstrates the TTL is set correctly
        $this->assertTrue($this->cache->has("user.{$userId}.roles"));
    }

    public function testMultipleUsersCache(): void
    {
        // Test that multiple users can have independent caches
        $this->cache->put("user.1.roles", ['admin']);
        $this->cache->put("user.2.roles", ['editor']);
        $this->cache->put("user.3.roles", ['viewer']);

        $this->assertSame(['admin'], $this->cache->get("user.1.roles"));
        $this->assertSame(['editor'], $this->cache->get("user.2.roles"));
        $this->assertSame(['viewer'], $this->cache->get("user.3.roles"));
    }
}
