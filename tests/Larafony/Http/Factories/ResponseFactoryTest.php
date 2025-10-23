<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Tests\Factories;

use Larafony\Framework\Http\Factories\ResponseFactory;
use PHPUnit\Framework\TestCase;

final class ResponseFactoryTest extends TestCase
{
    public function testCreateResponseWithDefaultValues(): void
    {
        $factory = new ResponseFactory();
        $response = $factory->createResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getReasonPhrase());
        $this->assertSame('', (string) $response->getBody());
    }

    public function testCreateResponseWithCustomStatusCode(): void
    {
        $factory = new ResponseFactory();
        $response = $factory->createResponse(404);

        $this->assertSame(404, $response->getStatusCode());

        $this->assertSame('Not Found', $response->getReasonPhrase());
    }

    public function testCreateResponseWithCustomReasonPhrase(): void
    {
        $factory = new ResponseFactory();
        $response = $factory->createResponse(200, 'Custom Reason');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Custom Reason', $response->getReasonPhrase());
    }

    public function testWithStatusCreatesNewInstance(): void
    {
        $factory = new ResponseFactory();
        $response = $factory->createResponse(200);
        $newResponse = $response->withStatus(404);

        $this->assertNotSame($response, $newResponse);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(404, $newResponse->getStatusCode());
        $this->assertSame('Not Found', $newResponse->getReasonPhrase());
    }

    public function testWithStatusAndCustomReasonPhrase(): void
    {
        $factory = new ResponseFactory();
        $response = $factory->createResponse();
        $newResponse = $response->withStatus(500, 'Custom Error');

        $this->assertSame(500, $newResponse->getStatusCode());
        $this->assertSame('Custom Error', $newResponse->getReasonPhrase());
    }

    public function testWithContentSetsBody(): void
    {
        $factory = new ResponseFactory();
        $response = $factory->createResponse();
        $withContent = $response->withContent('Hello, World!');

        $this->assertSame('Hello, World!', (string) $withContent->getBody());
    }

    public function testWithJsonSetsBodyAndContentType(): void
    {
        $factory = new ResponseFactory();
        $response = $factory->createResponse();
        $data = ['message' => 'success', 'code' => 123];
        $withJson = $response->withJson($data);

        $this->assertSame('{"message":"success","code":123}', (string) $withJson->getBody());
        $this->assertTrue($withJson->hasHeader('Content-Type'));
        $this->assertSame(['application/json'], $withJson->getHeader('Content-Type'));
    }

    public function testWithJsonSetsCustomStatusCode(): void
    {
        $factory = new ResponseFactory();
        $response = $factory->createResponse();
        $withJson = $response->withJson(['error' => 'not found'], 404);

        $this->assertSame(404, $withJson->getStatusCode());
        $this->assertSame('Not Found', $withJson->getReasonPhrase());
    }

    public function testRedirectSetsLocationHeader(): void
    {
        $factory = new ResponseFactory();
        $response = $factory->createResponse();
        $redirect = $response->redirect('/new-location');

        $this->assertSame(302, $redirect->getStatusCode());
        $this->assertTrue($redirect->hasHeader('Location'));
        $this->assertSame(['/new-location'], $redirect->getHeader('Location'));
    }

    public function testRedirectWithCustomStatusCode(): void
    {
        $factory = new ResponseFactory();
        $response = $factory->createResponse();
        $redirect = $response->redirect('/permanent', 301);

        $this->assertSame(301, $redirect->getStatusCode());
        $this->assertSame('Moved Permanently', $redirect->getReasonPhrase());
        $this->assertSame(['/permanent'], $redirect->getHeader('Location'));
    }

    public function testResponseInheritsFromMessage(): void
    {
        $factory = new ResponseFactory();
        $response = $factory->createResponse();

        $withHeader = $response->withHeader('X-Custom', 'value');
        $this->assertTrue($withHeader->hasHeader('X-Custom'));
        $this->assertSame(['value'], $withHeader->getHeader('X-Custom'));
    }

    public function testResponseSupportsProtocolVersion(): void
    {
        $factory = new ResponseFactory();
        $response = $factory->createResponse();

        $this->assertSame('1.1', $response->getProtocolVersion());

        $withVersion = $response->withProtocolVersion('2.0');
        $this->assertSame('2.0', $withVersion->getProtocolVersion());
    }

    public function testCreateJsonResponseWithDefaultStatusCode(): void
    {
        $factory = new ResponseFactory();
        $data = ['message' => 'success', 'id' => 123];
        $response = $factory->createJsonResponse($data);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getReasonPhrase());
        $this->assertTrue($response->hasHeader('Content-Type'));
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);
        $this->assertSame(['message' => 'success', 'id' => 123], $decoded);
    }

    public function testCreateJsonResponseWithCustomStatusCode(): void
    {
        $factory = new ResponseFactory();
        $data = ['error' => 'Not Found'];
        $response = $factory->createJsonResponse($data, 404);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('Not Found', $response->getReasonPhrase());

        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);
        $this->assertSame(['error' => 'Not Found'], $decoded);
    }

    public function testCreateJsonResponseWithCustomHeaders(): void
    {
        $factory = new ResponseFactory();
        $data = ['data' => 'test'];
        $headers = ['X-Custom-Header' => 'custom-value'];
        $response = $factory->createJsonResponse($data, 200, $headers);

        $this->assertTrue($response->hasHeader('X-Custom-Header'));
        $this->assertSame('custom-value', $response->getHeaderLine('X-Custom-Header'));
    }

    public function testCreateJsonResponseEncodesArrays(): void
    {
        $factory = new ResponseFactory();
        $data = [
            'users' => [
                ['id' => 1, 'name' => 'Alice'],
                ['id' => 2, 'name' => 'Bob'],
            ]
        ];
        $response = $factory->createJsonResponse($data);

        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('users', $decoded);
        $this->assertCount(2, $decoded['users']);
    }

    public function testCreateJsonResponseWith201Created(): void
    {
        $factory = new ResponseFactory();
        $data = ['id' => 999, 'created' => true];
        $response = $factory->createJsonResponse($data, 201);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('Created', $response->getReasonPhrase());
    }
}
