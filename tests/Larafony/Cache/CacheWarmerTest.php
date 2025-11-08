<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Cache;

use Larafony\Framework\Cache\Cache;
use Larafony\Framework\Cache\CacheItemPool;
use Larafony\Framework\Cache\CacheWarmer;
use Larafony\Framework\Cache\Storage\FileStorage;
use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Web\Application;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;

class CacheWarmerTest extends TestCase
{
    private Cache $cache;
    private CacheWarmer $warmer;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset singletons and test clock
        Application::empty();
        Cache::empty();
        \Larafony\Framework\Clock\SystemClock::withTestNow(null);

        // Create temporary directory for file cache
        $this->tempDir = sys_get_temp_dir() . '/cache_warmer_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);

        // Get application instance and mock config
        $app = Application::instance();
        $configMock = $this->createMock(ConfigContract::class);
        $configMock->method('get')
            ->willReturnMap([
                ['cache.default', null, 'file'],
                ['cache.stores.file', null, ['path' => $this->tempDir]],
            ]);
        $app->set(ConfigContract::class, $configMock);

        // Initialize cache directly (bypassing StorageFactory)
        $storage = new FileStorage($this->tempDir);
        $pool = new CacheItemPool($storage);

        // Don't use Cache::instance() as it creates new storage
        // Create Cache manually and init with our pool
        $this->cache = new Cache();
        $this->cache->init($pool);

        $this->warmer = new CacheWarmer($this->cache);
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
        \Larafony\Framework\Clock\SystemClock::withTestNow(null);
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

    public function testRegisterWarmer(): void
    {
        $this->warmer->register(
            key: 'test.key',
            callback: fn() => 'test value',
            ttl: 60
        );

        $this->assertSame(1, $this->warmer->count());
    }

    public function testRegisterMultipleWarmers(): void
    {
        $this->warmer
            ->register('key1', fn() => 'value1')
            ->register('key2', fn() => 'value2')
            ->register('key3', fn() => 'value3');

        $this->assertSame(3, $this->warmer->count());
    }

    public function testWarmSingleKey(): void
    {
        $value = 'test value';
        $key = 'test.key';

        $result = $this->warmer->warm($key, fn() => $value, 60);

        $this->assertTrue($result);
        $this->assertTrue($this->cache->has($key));
        $this->assertSame($value, $this->cache->get($key));
    }

    public function testWarmWithTags(): void
    {
        $value = 'tagged value';
        $key = 'test.tagged';
        $tags = ['users', 'statistics'];

        $result = $this->warmer->warm($key, fn() => $value, 60, $tags);

        $this->assertTrue($result);
        $this->assertTrue($this->cache->tags($tags)->has($key));
        $this->assertSame($value, $this->cache->tags($tags)->get($key));
    }

    public function testWarmAll(): void
    {
        $this->warmer
            ->register('key1', fn() => 'value1', 60)
            ->register('key2', fn() => 'value2', 60)
            ->register('key3', fn() => 'value3', 60);

        $result = $this->warmer->warmAll();

        $this->assertSame(3, $result['total']);
        $this->assertSame(3, $result['warmed']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame(0, $result['failed']);

        $this->assertTrue($this->cache->has('key1'));
        $this->assertTrue($this->cache->has('key2'));
        $this->assertTrue($this->cache->has('key3'));
    }

    public function testWarmAllSkipsExistingKeys(): void
    {
        // Pre-populate cache
        $this->cache->put('key1', 'existing value', 60);

        $this->warmer
            ->register('key1', fn() => 'new value', 60)
            ->register('key2', fn() => 'value2', 60);

        $result = $this->warmer->warmAll(force: false);

        $this->assertSame(2, $result['total']);
        $this->assertSame(1, $result['warmed']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame(0, $result['failed']);

        // Key1 should still have old value
        $this->assertSame('existing value', $this->cache->get('key1'));
        // Key2 should be warmed
        $this->assertSame('value2', $this->cache->get('key2'));
    }

    public function testWarmAllForceOverwritesExisting(): void
    {
        // Pre-populate cache
        $this->cache->put('key1', 'old value', 60);

        $this->warmer->register('key1', fn() => 'new value', 60);

        $result = $this->warmer->warmAll(force: true);

        $this->assertSame(1, $result['total']);
        $this->assertSame(1, $result['warmed']);
        $this->assertSame(0, $result['skipped']);

        // Should be overwritten
        $this->assertSame('new value', $this->cache->get('key1'));
    }

    public function testWarmAllHandlesFailures(): void
    {
        $this->warmer
            ->register('key1', fn() => 'value1', 60)
            ->register('key2', function () {
                throw new \RuntimeException('Test failure');
            }, 60)
            ->register('key3', fn() => 'value3', 60);

        $result = $this->warmer->warmAll();

        $this->assertSame(3, $result['total']);
        $this->assertSame(2, $result['warmed']);
        $this->assertSame(1, $result['failed']);

        // Successful warmers should still be cached
        $this->assertTrue($this->cache->has('key1'));
        $this->assertFalse($this->cache->has('key2'));
        $this->assertTrue($this->cache->has('key3'));
    }

    public function testWarmInBatches(): void
    {
        // Register 25 warmers
        for ($i = 1; $i <= 25; $i++) {
            $this->warmer->register("key{$i}", fn() => "value{$i}", 60);
        }

        $result = $this->warmer->warmInBatches(batchSize: 10);

        $this->assertSame(25, $result['total']);
        $this->assertSame(25, $result['warmed']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame(0, $result['failed']);
        $this->assertSame(3, $result['batches']); // 10 + 10 + 5

        // Verify all keys are cached
        for ($i = 1; $i <= 25; $i++) {
            $this->assertTrue($this->cache->has("key{$i}"));
        }
    }

    public function testClearWarmers(): void
    {
        $this->warmer
            ->register('key1', fn() => 'value1')
            ->register('key2', fn() => 'value2');

        $this->assertSame(2, $this->warmer->count());

        $this->warmer->clear();

        $this->assertSame(0, $this->warmer->count());
    }

    public function testComplexCallbackWithArray(): void
    {
        $data = [
            'users' => ['Alice', 'Bob', 'Charlie'],
            'count' => 3,
            'timestamp' => time(),
        ];

        $result = $this->warmer->warm('complex.data', fn() => $data, 60);

        $this->assertTrue($result);
        $this->assertSame($data, $this->cache->get('complex.data'));
    }

    public function testWarmWithNoTtl(): void
    {
        $result = $this->warmer->warm('forever.key', fn() => 'permanent value');

        $this->assertTrue($result);
        $this->assertTrue($this->cache->has('forever.key'));
        $this->assertSame('permanent value', $this->cache->get('forever.key'));
    }
}
