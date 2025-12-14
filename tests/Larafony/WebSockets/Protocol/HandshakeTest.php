<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\WebSockets\Protocol;

use Larafony\Framework\WebSockets\Protocol\Handshake;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Handshake::class)]
final class HandshakeTest extends TestCase
{
    public function testParsesValidRequest(): void
    {
        $request = "GET /chat HTTP/1.1\r\n" .
            "Host: localhost:8080\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==\r\n" .
            "Sec-WebSocket-Version: 13\r\n\r\n";

        $headers = Handshake::parseRequest($request);

        $this->assertIsArray($headers);
        $this->assertSame('localhost:8080', $headers['Host']);
        $this->assertSame('websocket', $headers['Upgrade']);
        $this->assertSame('dGhlIHNhbXBsZSBub25jZQ==', $headers['Sec-WebSocket-Key']);
    }

    public function testReturnsNullForIncompleteRequest(): void
    {
        $request = "GET /chat HTTP/1.1\r\nHost: localhost";

        $headers = Handshake::parseRequest($request);

        $this->assertNull($headers);
    }

    public function testCreatesCorrectAcceptKey(): void
    {
        $key = 'dGhlIHNhbXBsZSBub25jZQ==';

        $acceptKey = Handshake::createAcceptKey($key);

        $this->assertSame('s3pPLMBiTxaQ9kYGzzhZRbK+xOo=', $acceptKey);
    }

    public function testCreatesValidResponse(): void
    {
        $key = 'dGhlIHNhbXBsZSBub25jZQ==';

        $response = Handshake::createResponse($key);

        $this->assertStringContainsString('HTTP/1.1 101 Switching Protocols', $response);
        $this->assertStringContainsString('Upgrade: websocket', $response);
        $this->assertStringContainsString('Connection: Upgrade', $response);
        $this->assertStringContainsString('Sec-WebSocket-Accept: s3pPLMBiTxaQ9kYGzzhZRbK+xOo=', $response);
    }

    public function testCreatesErrorResponse(): void
    {
        $response = Handshake::createErrorResponse(400, 'Bad Request');

        $this->assertSame("HTTP/400 Bad Request\r\n\r\n", $response);
    }

    public function testValidatesUpgradeRequest(): void
    {
        $validHeaders = [
            'Upgrade' => 'websocket',
            'Sec-WebSocket-Key' => 'test-key',
        ];

        $this->assertTrue(Handshake::isValidUpgradeRequest($validHeaders));
    }

    public function testRejectsRequestWithoutKey(): void
    {
        $headers = [
            'Upgrade' => 'websocket',
        ];

        $this->assertFalse(Handshake::isValidUpgradeRequest($headers));
    }

    public function testRejectsRequestWithoutUpgrade(): void
    {
        $headers = [
            'Sec-WebSocket-Key' => 'test-key',
        ];

        $this->assertFalse(Handshake::isValidUpgradeRequest($headers));
    }

    public function testRejectsNonWebsocketUpgrade(): void
    {
        $headers = [
            'Upgrade' => 'http/2',
            'Sec-WebSocket-Key' => 'test-key',
        ];

        $this->assertFalse(Handshake::isValidUpgradeRequest($headers));
    }

    public function testAcceptsCaseInsensitiveUpgrade(): void
    {
        $headers = [
            'Upgrade' => 'WebSocket',
            'Sec-WebSocket-Key' => 'test-key',
        ];

        $this->assertTrue(Handshake::isValidUpgradeRequest($headers));
    }
}
