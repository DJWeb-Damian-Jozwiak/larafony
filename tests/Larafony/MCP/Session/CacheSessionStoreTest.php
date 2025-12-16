<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\MCP\Session;

use Larafony\Framework\Cache\Cache;
use Larafony\Framework\MCP\Session\CacheSessionStore;
use Mcp\Server\Session\SessionStoreInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class CacheSessionStoreTest extends TestCase
{
    private Cache $cacheMock;
    private CacheSessionStore $store;

    protected function setUp(): void
    {
        $this->cacheMock = $this->createMock(Cache::class);
        $this->store = new CacheSessionStore($this->cacheMock, 3600);
    }

    public function testImplementsSessionStoreInterface(): void
    {
        $this->assertInstanceOf(SessionStoreInterface::class, $this->store);
    }

    public function testExistsChecksCache(): void
    {
        $uuid = Uuid::v4();

        $this->cacheMock
            ->expects($this->once())
            ->method('has')
            ->with('mcp_session_' . $uuid->toRfc4122())
            ->willReturn(true);

        $this->assertTrue($this->store->exists($uuid));
    }

    public function testReadReturnsDataFromCache(): void
    {
        $uuid = Uuid::v4();
        $data = '{"key": "value"}';

        $this->cacheMock
            ->expects($this->once())
            ->method('get')
            ->with('mcp_session_' . $uuid->toRfc4122())
            ->willReturn($data);

        $result = $this->store->read($uuid);

        $this->assertSame($data, $result);
    }

    public function testReadReturnsFalseWhenNotFound(): void
    {
        $uuid = Uuid::v4();

        $this->cacheMock
            ->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $result = $this->store->read($uuid);

        $this->assertFalse($result);
    }

    public function testWriteSavesToCache(): void
    {
        $uuid = Uuid::v4();
        $data = '{"key": "value"}';

        $this->cacheMock
            ->expects($this->once())
            ->method('put')
            ->with('mcp_session_' . $uuid->toRfc4122(), $data, 3600)
            ->willReturn(true);

        $result = $this->store->write($uuid, $data);

        $this->assertTrue($result);
    }

    public function testDestroyRemovesFromCache(): void
    {
        $uuid = Uuid::v4();

        $this->cacheMock
            ->expects($this->once())
            ->method('forget')
            ->with('mcp_session_' . $uuid->toRfc4122())
            ->willReturn(true);

        $result = $this->store->destroy($uuid);

        $this->assertTrue($result);
    }

    public function testGcReturnsEmptyArrayByDefault(): void
    {
        $result = $this->store->gc();

        $this->assertSame([], $result);
    }

    public function testGcRemovesExpiredSessions(): void
    {
        $uuid = Uuid::v4();

        $this->cacheMock->method('put')->willReturn(true);
        $this->cacheMock->method('forget')->willReturn(true);

        // Create store with 0 TTL to simulate immediate expiration
        $store = new CacheSessionStore($this->cacheMock, 0);
        $store->write($uuid, 'data');

        // Wait a moment for time to pass (GC checks now - timestamp > ttl)
        sleep(1);

        $expired = $store->gc();

        $this->assertCount(1, $expired);
        $this->assertEquals($uuid->toRfc4122(), $expired[0]->toRfc4122());
    }
}
