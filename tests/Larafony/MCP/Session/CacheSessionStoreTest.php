<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\MCP\Session;

use Larafony\Framework\Cache\Cache;
use Larafony\Framework\MCP\Session\CacheSessionStore;
use Mcp\Server\Session\SessionStoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class CacheSessionStoreTest extends TestCase
{
    private function createStoreWithMock(): array
    {
        $cacheMock = $this->createMock(Cache::class);
        $store = new CacheSessionStore($cacheMock, 3600);
        return [$store, $cacheMock];
    }

    public function testImplementsSessionStoreInterface(): void
    {
        $cacheStub = $this->createStub(Cache::class);
        $store = new CacheSessionStore($cacheStub, 3600);
        $this->assertInstanceOf(SessionStoreInterface::class, $store);
    }

    public function testExistsChecksCache(): void
    {
        [$store, $cacheMock] = $this->createStoreWithMock();
        $uuid = Uuid::v4();

        $cacheMock
            ->expects($this->once())
            ->method('has')
            ->with('mcp_session_' . $uuid->toRfc4122())
            ->willReturn(true);

        $this->assertTrue($store->exists($uuid));
    }

    public function testReadReturnsDataFromCache(): void
    {
        [$store, $cacheMock] = $this->createStoreWithMock();
        $uuid = Uuid::v4();
        $data = '{"key": "value"}';

        $cacheMock
            ->expects($this->once())
            ->method('get')
            ->with('mcp_session_' . $uuid->toRfc4122())
            ->willReturn($data);

        $result = $store->read($uuid);

        $this->assertSame($data, $result);
    }

    public function testReadReturnsFalseWhenNotFound(): void
    {
        [$store, $cacheMock] = $this->createStoreWithMock();
        $uuid = Uuid::v4();

        $cacheMock
            ->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $result = $store->read($uuid);

        $this->assertFalse($result);
    }

    public function testWriteSavesToCache(): void
    {
        [$store, $cacheMock] = $this->createStoreWithMock();
        $uuid = Uuid::v4();
        $data = '{"key": "value"}';

        $cacheMock
            ->expects($this->once())
            ->method('put')
            ->with('mcp_session_' . $uuid->toRfc4122(), $data, 3600)
            ->willReturn(true);

        $result = $store->write($uuid, $data);

        $this->assertTrue($result);
    }

    public function testDestroyRemovesFromCache(): void
    {
        [$store, $cacheMock] = $this->createStoreWithMock();
        $uuid = Uuid::v4();

        $cacheMock
            ->expects($this->once())
            ->method('forget')
            ->with('mcp_session_' . $uuid->toRfc4122())
            ->willReturn(true);

        $result = $store->destroy($uuid);

        $this->assertTrue($result);
    }

    public function testGcReturnsEmptyArrayByDefault(): void
    {
        $cacheStub = $this->createStub(Cache::class);
        $store = new CacheSessionStore($cacheStub, 3600);
        $result = $store->gc();

        $this->assertSame([], $result);
    }

    public function testGcRemovesExpiredSessions(): void
    {
        $uuid = Uuid::v4();

        $cacheStub = $this->createStub(Cache::class);
        $cacheStub->method('put')->willReturn(true);
        $cacheStub->method('forget')->willReturn(true);

        // Create store with 0 TTL to simulate immediate expiration
        $store = new CacheSessionStore($cacheStub, 0);
        $store->write($uuid, 'data');

        // Wait a moment for time to pass (GC checks now - timestamp > ttl)
        sleep(1);

        $expired = $store->gc();

        $this->assertCount(1, $expired);
        $this->assertEquals($uuid->toRfc4122(), $expired[0]->toRfc4122());
    }
}
