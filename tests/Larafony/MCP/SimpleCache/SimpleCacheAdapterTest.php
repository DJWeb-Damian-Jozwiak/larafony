<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\MCP\SimpleCache;

use Larafony\Framework\Cache\Cache;
use Larafony\Framework\MCP\SimpleCache\SimpleCacheAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

final class SimpleCacheAdapterTest extends TestCase
{
    private function createAdapterWithMock(): array
    {
        $cacheMock = $this->createMock(Cache::class);
        $adapter = new SimpleCacheAdapter($cacheMock);
        return [$adapter, $cacheMock];
    }

    public function testImplementsCacheInterface(): void
    {
        $cacheStub = $this->createStub(Cache::class);
        $adapter = new SimpleCacheAdapter($cacheStub);
        $this->assertInstanceOf(CacheInterface::class, $adapter);
    }

    public function testGetDelegatesToCache(): void
    {
        [$adapter, $cacheMock] = $this->createAdapterWithMock();

        $cacheMock
            ->expects($this->once())
            ->method('get')
            ->with('test_key', 'default')
            ->willReturn('test_value');

        $result = $adapter->get('test_key', 'default');

        $this->assertSame('test_value', $result);
    }

    public function testSetDelegatesToCache(): void
    {
        [$adapter, $cacheMock] = $this->createAdapterWithMock();

        $cacheMock
            ->expects($this->once())
            ->method('put')
            ->with('test_key', 'test_value', 3600)
            ->willReturn(true);

        $result = $adapter->set('test_key', 'test_value', 3600);

        $this->assertTrue($result);
    }

    public function testDeleteDelegatesToCache(): void
    {
        [$adapter, $cacheMock] = $this->createAdapterWithMock();

        $cacheMock
            ->expects($this->once())
            ->method('forget')
            ->with('test_key')
            ->willReturn(true);

        $result = $adapter->delete('test_key');

        $this->assertTrue($result);
    }

    public function testClearDelegatesToCache(): void
    {
        [$adapter, $cacheMock] = $this->createAdapterWithMock();

        $cacheMock
            ->expects($this->once())
            ->method('clear')
            ->willReturn(true);

        $result = $adapter->clear();

        $this->assertTrue($result);
    }

    public function testHasDelegatesToCache(): void
    {
        [$adapter, $cacheMock] = $this->createAdapterWithMock();

        $cacheMock
            ->expects($this->once())
            ->method('has')
            ->with('test_key')
            ->willReturn(true);

        $result = $adapter->has('test_key');

        $this->assertTrue($result);
    }

    public function testGetMultipleReturnsGenerator(): void
    {
        [$adapter, $cacheMock] = $this->createAdapterWithMock();

        $cacheMock
            ->expects($this->once())
            ->method('getMultiple')
            ->with(['key1', 'key2'])
            ->willReturn(['key1' => 'value1', 'key2' => null]);

        $result = iterator_to_array($adapter->getMultiple(['key1', 'key2'], 'default'));

        $this->assertSame(['key1' => 'value1', 'key2' => 'default'], $result);
    }

    public function testSetMultipleDelegatesToCache(): void
    {
        [$adapter, $cacheMock] = $this->createAdapterWithMock();
        $values = ['key1' => 'value1', 'key2' => 'value2'];

        $cacheMock
            ->expects($this->once())
            ->method('putMultiple')
            ->with($values, 3600)
            ->willReturn(true);

        $result = $adapter->setMultiple($values, 3600);

        $this->assertTrue($result);
    }

    public function testDeleteMultipleDelegatesToCache(): void
    {
        [$adapter, $cacheMock] = $this->createAdapterWithMock();
        $keys = ['key1', 'key2'];

        $cacheMock
            ->expects($this->once())
            ->method('forgetMultiple')
            ->with($keys)
            ->willReturn(true);

        $result = $adapter->deleteMultiple($keys);

        $this->assertTrue($result);
    }
}
