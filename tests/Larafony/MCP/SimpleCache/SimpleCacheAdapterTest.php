<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\MCP\SimpleCache;

use Larafony\Framework\Cache\Cache;
use Larafony\Framework\MCP\SimpleCache\SimpleCacheAdapter;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

final class SimpleCacheAdapterTest extends TestCase
{
    private Cache $cacheMock;
    private SimpleCacheAdapter $adapter;

    protected function setUp(): void
    {
        $this->cacheMock = $this->createMock(Cache::class);
        $this->adapter = new SimpleCacheAdapter($this->cacheMock);
    }

    public function testImplementsCacheInterface(): void
    {
        $this->assertInstanceOf(CacheInterface::class, $this->adapter);
    }

    public function testGetDelegatesToCache(): void
    {
        $this->cacheMock
            ->expects($this->once())
            ->method('get')
            ->with('test_key', 'default')
            ->willReturn('test_value');

        $result = $this->adapter->get('test_key', 'default');

        $this->assertSame('test_value', $result);
    }

    public function testSetDelegatesToCache(): void
    {
        $this->cacheMock
            ->expects($this->once())
            ->method('put')
            ->with('test_key', 'test_value', 3600)
            ->willReturn(true);

        $result = $this->adapter->set('test_key', 'test_value', 3600);

        $this->assertTrue($result);
    }

    public function testDeleteDelegatesToCache(): void
    {
        $this->cacheMock
            ->expects($this->once())
            ->method('forget')
            ->with('test_key')
            ->willReturn(true);

        $result = $this->adapter->delete('test_key');

        $this->assertTrue($result);
    }

    public function testClearDelegatesToCache(): void
    {
        $this->cacheMock
            ->expects($this->once())
            ->method('clear')
            ->willReturn(true);

        $result = $this->adapter->clear();

        $this->assertTrue($result);
    }

    public function testHasDelegatesToCache(): void
    {
        $this->cacheMock
            ->expects($this->once())
            ->method('has')
            ->with('test_key')
            ->willReturn(true);

        $result = $this->adapter->has('test_key');

        $this->assertTrue($result);
    }

    public function testGetMultipleReturnsGenerator(): void
    {
        $this->cacheMock
            ->expects($this->once())
            ->method('getMultiple')
            ->with(['key1', 'key2'])
            ->willReturn(['key1' => 'value1', 'key2' => null]);

        $result = iterator_to_array($this->adapter->getMultiple(['key1', 'key2'], 'default'));

        $this->assertSame(['key1' => 'value1', 'key2' => 'default'], $result);
    }

    public function testSetMultipleDelegatesToCache(): void
    {
        $values = ['key1' => 'value1', 'key2' => 'value2'];

        $this->cacheMock
            ->expects($this->once())
            ->method('putMultiple')
            ->with($values, 3600)
            ->willReturn(true);

        $result = $this->adapter->setMultiple($values, 3600);

        $this->assertTrue($result);
    }

    public function testDeleteMultipleDelegatesToCache(): void
    {
        $keys = ['key1', 'key2'];

        $this->cacheMock
            ->expects($this->once())
            ->method('forgetMultiple')
            ->with($keys)
            ->willReturn(true);

        $result = $this->adapter->deleteMultiple($keys);

        $this->assertTrue($result);
    }
}
