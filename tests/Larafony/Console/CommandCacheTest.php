<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Larafony\Console;

use Larafony\Framework\Console\Command;
use Larafony\Framework\Console\CommandCache;
use Larafony\Framework\Console\Contracts\OutputContract;
use PHPUnit\Framework\TestCase;

final class CommandCacheTest extends TestCase
{
    private CommandCache $cache;
    private string $tempCacheFile;

    protected function setUp(): void
    {
        $this->cache = new CommandCache();
        $this->tempCacheFile = sys_get_temp_dir() . '/test_commands_cache_' . uniqid() . '.php';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempCacheFile)) {
            unlink($this->tempCacheFile);
        }
    }

    public function testLoadReturnsFalseWhenCacheFileDoesNotExist(): void
    {
        $result = $this->cache->load('/nonexistent/cache.php');

        $this->assertFalse($result);
    }

    public function testLoadReturnsFalseWhenCacheFileIsNotArray(): void
    {
        file_put_contents($this->tempCacheFile, '<?php return "not an array";');

        $result = $this->cache->load($this->tempCacheFile);

        $this->assertFalse($result);
    }

    public function testLoadSucceedsWithValidCacheFile(): void
    {
        $cacheContent = <<<'PHP'
        <?php
        return [
            'test' => 'Larafony\Framework\Tests\Larafony\Console\CacheTestCommand',
        ];
        PHP;

        file_put_contents($this->tempCacheFile, $cacheContent);

        $result = $this->cache->load($this->tempCacheFile);

        $this->assertTrue($result);
        $this->assertArrayHasKey('test', $this->cache->commands);
    }

    public function testSaveCreatesCacheFile(): void
    {
        $this->cache->save($this->tempCacheFile);

        $this->assertFileExists($this->tempCacheFile);
    }

    public function testSaveCreatesDirectoryIfNotExists(): void
    {
        $cacheDir = sys_get_temp_dir() . '/test_cache_dir_' . uniqid();
        $cacheFile = $cacheDir . '/commands.php';

        $this->cache->save($cacheFile);

        $this->assertFileExists($cacheFile);

        // Cleanup
        unlink($cacheFile);
        rmdir($cacheDir);
    }

    public function testLoadFiltersNonExistentClasses(): void
    {
        $cacheContent = <<<'PHP'
        <?php
        return [
            'test' => 'Larafony\Framework\Tests\Larafony\Console\CacheTestCommand',
            'nonexistent' => 'NonExistent\Command',
        ];
        PHP;

        file_put_contents($this->tempCacheFile, $cacheContent);

        $result = $this->cache->load($this->tempCacheFile);

        $this->assertTrue($result);
        $this->assertCount(1, $this->cache->commands);
        $this->assertArrayHasKey('test', $this->cache->commands);
        $this->assertArrayNotHasKey('nonexistent', $this->cache->commands);
    }
}

class CacheTestCommand extends Command
{
    public function run(): int
    {
        return 0;
    }
}
