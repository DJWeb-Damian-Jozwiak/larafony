<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Cache\Storage;

use Larafony\Framework\Cache\Storage\FileStorage;
use Larafony\Framework\Cache\Storage\MemcachedStorage;
use Larafony\Framework\Cache\Storage\RedisStorage;
use Larafony\Framework\Cache\Storage\StorageContract;
use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Web\Application;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StorageTest extends TestCase
{
    private static string $tempDir;
    private static ?\Redis $redis = null;
    private static ?\Memcached $memcached = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        // Initialization moved to storageProvider() since it's called before setUpBeforeClass
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        // Clean up file storage
        self::removeDirectory(self::$tempDir);

        // Clean up Redis
        if (self::$redis) {
            self::$redis->flushDB();
            self::$redis->close();
        }

        // Clean up Memcached
        if (self::$memcached) {
            self::$memcached->flush();
        }
    }

    private static function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? self::removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public static function storageProvider(): array
    {
        // Initialize temp directory
        self::$tempDir = sys_get_temp_dir() . '/storage_test_' . uniqid();
        mkdir(self::$tempDir, 0777, true);

        $providers = [
            'file' => [
                'type' => 'file',
                'factory' => fn() => new FileStorage(self::$tempDir),
            ],
        ];

        // Try to initialize Redis
        if (extension_loaded('redis')) {
            try {
                $redis = new \Redis();
                $redisHost = getenv('REDIS_HOST') ?: '127.0.0.1';
                $redisPort = (int) (getenv('REDIS_PORT') ?: 6379);
                $redis->connect($redisHost, $redisPort);
                $redis->select(15); // Use test database
                $redis->flushDB(); // Clear test database
                self::$redis = $redis;

                $providers['redis'] = [
                    'type' => 'redis',
                    'factory' => fn() => new RedisStorage(self::$redis, 'test:'),
                ];
            } catch (\RedisException $e) {
                // Redis not available
            }
        }

        // Try to initialize Memcached
        if (extension_loaded('memcached')) {
            try {
                $memcached = new \Memcached();
                $memcachedHost = getenv('MEMCACHED_HOST') ?: '127.0.0.1';
                $memcachedPort = (int) (getenv('MEMCACHED_PORT') ?: 11211);
                $memcached->addServer($memcachedHost, $memcachedPort);
                $memcached->set('test_connection', true);
                if ($memcached->getResultCode() === \Memcached::RES_SUCCESS) {
                    self::$memcached = $memcached;

                    $providers['memcached'] = [
                        'type' => 'memcached',
                        'factory' => function() {
                            // Mock ConfigContract for MemcachedStorage
                            $test = new self('test');
                            $configMock = $test->createMock(ConfigContract::class);
                            $configMock->method('get')
                                ->willReturn('test:');

                            $app = Application::instance();
                            $app->set(ConfigContract::class, $configMock);

                            return new MemcachedStorage(self::$memcached);
                        },
                    ];
                }
            } catch (\Exception $e) {
                // Memcached not available
            }
        }

        return $providers;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clear all storages after each test
        if (self::$redis) {
            self::$redis->flushDB();
        }
        if (self::$memcached) {
            self::$memcached->flush();
        }
        if (is_dir(self::$tempDir)) {
            $files = array_diff(scandir(self::$tempDir), ['.', '..']);
            foreach ($files as $file) {
                unlink(self::$tempDir . '/' . $file);
            }
        }

        // Reset Application singleton to clear config mock
        Application::empty();
    }

    #[DataProvider('storageProvider')]
    public function testSetAndGet(string $type, callable $factory): void
    {
        $storage = $factory();

        $data = [
            'value' => 'test_value',
            'expiry' => time() + 3600,
        ];

        $result = $storage->set('test_key', $data);
        $this->assertTrue($result, "[$type] Failed to set cache item");

        $retrieved = $storage->get('test_key');
        $this->assertNotNull($retrieved, "[$type] Retrieved data is null");
        $this->assertSame('test_value', $retrieved['value'], "[$type] Value mismatch");
    }

    #[DataProvider('storageProvider')]
    public function testGetNonExistent(string $type, callable $factory): void
    {
        $storage = $factory();

        $result = $storage->get('non_existent_key');
        $this->assertNull($result, "[$type] Non-existent key should return null");
    }

    #[DataProvider('storageProvider')]
    public function testHas(string $type, callable $factory): void
    {
        $storage = $factory();

        $this->assertNull($storage->get('test_key'), "[$type] Key should not exist initially");

        $storage->set('test_key', ['value' => 'data', 'expiry' => time() + 3600]);

        $this->assertNotNull($storage->get('test_key'), "[$type] Key should exist after setting");
    }

    #[DataProvider('storageProvider')]
    public function testDelete(string $type, callable $factory): void
    {
        $storage = $factory();

        $storage->set('test_key', ['value' => 'data', 'expiry' => time() + 3600]);
        $this->assertNotNull($storage->get('test_key'), "[$type] Key should exist before delete");

        $result = $storage->delete('test_key');
        $this->assertTrue($result, "[$type] Delete should return true");
        $this->assertNull($storage->get('test_key'), "[$type] Key should not exist after delete");
    }

    #[DataProvider('storageProvider')]
    public function testDeleteNonExistent(string $type, callable $factory): void
    {
        $storage = $factory();

        $result = $storage->delete('non_existent_key');
        $this->assertTrue($result, "[$type] Deleting non-existent key should return true");
    }

    #[DataProvider('storageProvider')]
    public function testClear(string $type, callable $factory): void
    {
        $storage = $factory();

        // Set multiple items
        $storage->set('key1', ['value' => 'value1', 'expiry' => time() + 3600]);
        $storage->set('key2', ['value' => 'value2', 'expiry' => time() + 3600]);
        $storage->set('key3', ['value' => 'value3', 'expiry' => time() + 3600]);

        $this->assertNotNull($storage->get('key1'), "[$type] key1 should exist");
        $this->assertNotNull($storage->get('key2'), "[$type] key2 should exist");
        $this->assertNotNull($storage->get('key3'), "[$type] key3 should exist");

        $result = $storage->clear();
        $this->assertTrue($result, "[$type] Clear should return true");

        // Memcached flush() is asynchronous - need small delay
        if ($type === 'memcached') {
            usleep(100000); // 100ms
        }

        $this->assertNull($storage->get('key1'), "[$type] key1 should not exist after clear");
        $this->assertNull($storage->get('key2'), "[$type] key2 should not exist after clear");
        $this->assertNull($storage->get('key3'), "[$type] key3 should not exist after clear");
    }

    #[DataProvider('storageProvider')]
    public function testExpiredItems(string $type, callable $factory): void
    {
        $storage = $factory();

        // Note: Storage layer doesn't check expiry - that's CacheItemPool's responsibility
        // However, Memcached automatically removes expired items (unlike File/Redis)
        $storage->set('expired_key', ['value' => 'data', 'expiry' => time() - 10]);

        $retrieved = $storage->get('expired_key');

        if ($type === 'memcached') {
            // Memcached automatically removes expired items
            $this->assertNull($retrieved, "[$type] Memcached should auto-remove expired items");
        } else {
            // File and Redis store expired data (CacheItemPool filters it)
            $this->assertNotNull($retrieved, "[$type] Storage should return data regardless of expiry");
            $this->assertSame('data', $retrieved['value'], "[$type] Value should match");
            $this->assertTrue($retrieved['expiry'] < time(), "[$type] Expiry timestamp should be in the past");
        }
    }

    #[DataProvider('storageProvider')]
    public function testNoExpiry(string $type, callable $factory): void
    {
        $storage = $factory();

        // Set item without expiry
        $storage->set('no_expiry_key', ['value' => 'persistent_data']);

        $retrieved = $storage->get('no_expiry_key');
        $this->assertNotNull($retrieved, "[$type] Key without expiry should exist");
        $this->assertSame('persistent_data', $retrieved['value'], "[$type] Value should match");
    }

    #[DataProvider('storageProvider')]
    public function testMultipleOperations(string $type, callable $factory): void
    {
        $storage = $factory();

        // Set multiple items
        $items = [
            'user.1' => ['value' => ['name' => 'Alice'], 'expiry' => time() + 3600],
            'user.2' => ['value' => ['name' => 'Bob'], 'expiry' => time() + 3600],
            'user.3' => ['value' => ['name' => 'Charlie'], 'expiry' => time() + 3600],
        ];

        foreach ($items as $key => $data) {
            $storage->set($key, $data);
        }

        // Verify all exist
        $this->assertNotNull($storage->get('user.1'), "[$type] user.1 should exist");
        $this->assertNotNull($storage->get('user.2'), "[$type] user.2 should exist");
        $this->assertNotNull($storage->get('user.3'), "[$type] user.3 should exist");

        // Verify values
        $user1 = $storage->get('user.1');
        $this->assertSame('Alice', $user1['value']['name'], "[$type] user.1 name should be Alice");

        // Delete one
        $storage->delete('user.2');
        $this->assertNull($storage->get('user.2'), "[$type] user.2 should not exist after delete");
        $this->assertNotNull($storage->get('user.1'), "[$type] user.1 should still exist");
        $this->assertNotNull($storage->get('user.3'), "[$type] user.3 should still exist");
    }

    #[DataProvider('storageProvider')]
    public function testComplexData(string $type, callable $factory): void
    {
        $storage = $factory();

        $complexData = [
            'value' => [
                'string' => 'text',
                'number' => 42,
                'float' => 3.14,
                'bool' => true,
                'null' => null,
                'array' => [1, 2, 3],
                'nested' => [
                    'key1' => 'value1',
                    'key2' => ['deep' => 'data'],
                ],
            ],
            'expiry' => time() + 3600,
        ];

        $storage->set('complex', $complexData);

        $retrieved = $storage->get('complex');
        $this->assertSame($complexData['value'], $retrieved['value'], "[$type] Complex data should match exactly");
    }

    #[DataProvider('storageProvider')]
    public function testLargeData(string $type, callable $factory): void
    {
        $storage = $factory();

        // Create large string (20KB)
        $largeString = str_repeat('Lorem ipsum dolor sit amet. ', 1000);
        $data = [
            'value' => $largeString,
            'expiry' => time() + 3600,
        ];

        $result = $storage->set('large_key', $data);
        $this->assertTrue($result, "[$type] Failed to set large data");

        $retrieved = $storage->get('large_key');
        $this->assertNotNull($retrieved, "[$type] Large data retrieved is null");
        $this->assertSame($largeString, $retrieved['value'], "[$type] Large data should match");
    }

    #[DataProvider('storageProvider')]
    public function testSpecialCharactersInKeys(string $type, callable $factory): void
    {
        $storage = $factory();

        $keys = [
            'user.123.profile',
            'cache_item_v2',
            'tag.admin.users',
            'key-with-dashes',
            'key_with_underscores',
        ];

        foreach ($keys as $key) {
            $data = ['value' => "data_for_{$key}", 'expiry' => time() + 3600];
            $storage->set($key, $data);

            $retrieved = $storage->get($key);
            $this->assertNotNull($retrieved, "[$type] Key '$key' should exist");
            $this->assertSame("data_for_{$key}", $retrieved['value'], "[$type] Value for key '$key' should match");
        }
    }

    #[DataProvider('storageProvider')]
    public function testOverwriteExisting(string $type, callable $factory): void
    {
        $storage = $factory();

        $storage->set('key', ['value' => 'original', 'expiry' => time() + 3600]);
        $retrieved = $storage->get('key');
        $this->assertSame('original', $retrieved['value'], "[$type] Original value should match");

        $storage->set('key', ['value' => 'updated', 'expiry' => time() + 3600]);
        $retrieved = $storage->get('key');
        $this->assertSame('updated', $retrieved['value'], "[$type] Updated value should match");
    }

    #[DataProvider('storageProvider')]
    public function testEmptyValue(string $type, callable $factory): void
    {
        $storage = $factory();

        $data = ['value' => '', 'expiry' => time() + 3600];
        $storage->set('empty', $data);

        $retrieved = $storage->get('empty');
        $this->assertSame('', $retrieved['value'], "[$type] Empty string should be preserved");
    }

    #[DataProvider('storageProvider')]
    public function testZeroValue(string $type, callable $factory): void
    {
        $storage = $factory();

        $data = ['value' => 0, 'expiry' => time() + 3600];
        $storage->set('zero', $data);

        $retrieved = $storage->get('zero');
        $this->assertSame(0, $retrieved['value'], "[$type] Zero value should be preserved");
    }

    #[DataProvider('storageProvider')]
    public function testFalseValue(string $type, callable $factory): void
    {
        $storage = $factory();

        $data = ['value' => false, 'expiry' => time() + 3600];
        $storage->set('false', $data);

        $retrieved = $storage->get('false');
        $this->assertFalse($retrieved['value'], "[$type] False value should be preserved");
    }

    #[DataProvider('storageProvider')]
    public function testNullValue(string $type, callable $factory): void
    {
        $storage = $factory();

        $data = ['value' => null, 'expiry' => time() + 3600];
        $storage->set('null', $data);

        $retrieved = $storage->get('null');
        $this->assertNull($retrieved['value'], "[$type] Null value should be preserved");
    }
}
