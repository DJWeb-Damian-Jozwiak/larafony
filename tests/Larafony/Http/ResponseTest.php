<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Http;

use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Http\Helpers\Request\HeaderManager;
use Larafony\Framework\Http\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ResponseTest extends TestCase
{
    private ResponseInterface $response;
    protected function setUp(): void
    {
        $this->response = new ResponseFactory()->createResponse()->withHeader('Content-Type', 'application/json');
    }

    public function testGetStatusCode(): void
    {
        $this->assertEquals(200, $this->response->getStatusCode());
    }

    public function testWithStatus(): void
    {
        $new = $this->response->withStatus(404, 'Not Found');
        $this->assertNotSame($this->response, $new);
        $this->assertEquals(404, $new->getStatusCode());
        $this->assertEquals('Not Found', $new->getReasonPhrase());
        $this->assertEquals(200, $this->response->getStatusCode());
    }

    public function testWithStatusDefaultReason(): void
    {
        $new = $this->response->withStatus(301);
        $this->assertEquals(301, $new->getStatusCode());
        $this->assertEquals('Moved Permanently', $new->getReasonPhrase());
    }

    public function testGetReasonPhrase(): void
    {
        $this->assertEquals('OK', $this->response->getReasonPhrase());
    }

    public function testGetProtocolVersion(): void
    {
        $this->assertEquals('1.1', $this->response->getProtocolVersion());
    }

    public function testWithProtocolVersion(): void
    {
        $new = $this->response->withProtocolVersion('2.0');
        $this->assertNotSame($this->response, $new);
        $this->assertEquals('2.0', $new->getProtocolVersion());
        $this->assertEquals('1.1', $this->response->getProtocolVersion());
    }

    public function testGetHeaders(): void
    {
        $expected = ['content-type' => ['application/json']];
        $this->assertEquals($expected, $this->response->getHeaders());
    }

    public function testWithContent ()
    {
        $response = $this->response->withContent('Hello World');
        $this->assertEquals('Hello World', (string)$response->getBody());
        $this->assertEquals('', $this->response->getBody()->getContents());//original body is empty
    }

    public function testHasHeader(): void
    {
        $this->assertTrue($this->response->hasHeader('Content-Type'));
        $this->assertFalse($this->response->hasHeader('X-Custom'));
    }

    public function testGetHeader(): void
    {
        $this->assertEquals(['application/json'],
            $this->response->getHeader('Content-Type'));
        $this->assertEquals([], $this->response->getHeader('X-Custom'));
    }

    public function testGetHeaderLine(): void
    {
        $this->assertEquals(
            'application/json',
            $this->response->getHeaderLine('Content-Type'),
        );
        $this->assertEquals('', $this->response->getHeaderLine('X-Custom'));
    }

    public function testWithHeader(): void
    {
        $new = $this->response->withHeader('X-Custom', 'value');
        $this->assertNotSame($this->response, $new);
        $this->assertTrue($new->hasHeader('X-Custom'));
        $this->assertEquals(['value'], $new->getHeader('X-Custom'));
        $this->assertFalse($this->response->hasHeader('X-Custom'));
    }

    public function testWithAddedHeader(): void
    {
        $new = $this->response->withAddedHeader('Content-Type', 'text/html');
        $this->assertNotSame($this->response, $new);
        $this->assertEquals(['application/json', 'text/html'],
            $new->getHeader('Content-Type'));
        $this->assertEquals(['application/json'],
            $this->response->getHeader('Content-Type'));
    }

    public function testWithoutHeader(): void
    {
        $new = $this->response->withoutHeader('Content-Type');
        $this->assertNotSame($this->response, $new);
        $this->assertFalse($new->hasHeader('Content-Type'));
        $this->assertTrue($this->response->hasHeader('Content-Type'));
    }

    public function testWithBody(): void
    {
        $newBody = $this->createStub(StreamInterface::class);
        $new = $this->response->withBody($newBody);
        $this->assertNotSame($this->response, $new);
    }

    public function testWithJson(): void
    {
        $data = ['key' => 'value'];
        $new = $this->response->withJson($data);
        $this->assertNotSame($this->response, $new);
        $this->assertEquals(
            'application/json',
            $new->getHeaderLine('Content-Type'),
        );
        $this->assertEquals(json_encode($data), (string)$new->getBody());
    }

    public function testWithJsonOptions(): void
    {
        $data = ['key' => 'value'];
        $new = $this->response->withJson($data, 201);
        $this->assertEquals(201, $new->getStatusCode());
        $this->assertJson(
            json_encode($data, JSON_PRETTY_PRINT),
            (string)$new->getBody(),
        );
    }

    public function testRedirect(): void
    {
        $new = $this->response->redirect('https://example.com', 301);
        $this->assertNotSame($this->response, $new);
        $this->assertEquals(301, $new->getStatusCode());
        $this->assertEquals(
            'https://example.com',
            $new->getHeaderLine('Location'),
        );
    }

    public function testDefaultRedirectStatus(): void
    {
        $new = $this->response->redirect('https://example.com');
        $this->assertEquals(302, $new->getStatusCode());
    }
}