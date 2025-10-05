<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Tests;

use Larafony\Framework\Http\JsonResponse;
use PHPUnit\Framework\TestCase;

final class JsonResponseTest extends TestCase
{
    public function testCreateJsonResponseWithArray(): void
    {
        $data = ['message' => 'success', 'code' => 200];
        $response = new JsonResponse($data);

        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($data, $decoded);
    }

    public function testSetsContentTypeToApplicationJson(): void
    {
        $response = new JsonResponse(['test' => 'value']);

        $this->assertTrue($response->hasHeader('Content-Type'));
        $contentType = $response->getHeader('Content-Type')[0];
        $this->assertStringContainsString('application/json', $contentType);
        $this->assertStringContainsString('charset=utf-8', $contentType);
    }

    public function testCreatesResponseWithCustomStatusCode(): void
    {
        $response = new JsonResponse(['error' => 'not found'], 404);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('Not Found', $response->getReasonPhrase());
    }

    public function testCreatesResponseWithCustomHeaders(): void
    {
        $headers = ['X-Custom-Header' => 'custom-value'];
        $response = new JsonResponse(['data' => 'test'], 200, $headers);

        $this->assertTrue($response->hasHeader('X-Custom-Header'));
        $this->assertSame(['custom-value'], $response->getHeader('X-Custom-Header'));
    }

    public function testCreatesResponseWithCustomProtocolVersion(): void
    {
        $response = new JsonResponse(['data' => 'test'], 200, [], '2.0');

        $this->assertSame('2.0', $response->getProtocolVersion());
    }

    public function testEncodesDataWithJsonFlags(): void
    {
        $data = ['html' => '<div>Test</div>', "quote" => "it's", 'amp' => 'A&B'];
        $response = new JsonResponse($data);

        $body = (string) $response->getBody();

        // JSON_HEX_TAG encodes < and >
        $this->assertStringContainsString('\u003C', $body);
        $this->assertStringContainsString('\u003E', $body);

        // JSON_HEX_APOS encodes '
        $this->assertStringContainsString('\u0027', $body);

        // JSON_HEX_AMP encodes &
        $this->assertStringContainsString('\u0026', $body);
    }

    public function testThrowsExceptionOnInvalidJson(): void
    {
        $this->expectException(\JsonException::class);

        // Create resource which cannot be JSON encoded
        $resource = fopen('php://memory', 'r');
        new JsonResponse(['resource' => $resource]);
        fclose($resource);
    }

    public function testInheritsBehaviorFromResponse(): void
    {
        $response = new JsonResponse(['data' => 'test']);

        $withStatus = $response->withStatus(201);
        $this->assertSame(201, $withStatus->getStatusCode());

        $withHeader = $response->withHeader('X-Test', 'value');
        $this->assertTrue($withHeader->hasHeader('X-Test'));
    }

    public function testEncodesNestedArrays(): void
    {
        $data = [
            'user' => [
                'name' => 'John',
                'roles' => ['admin', 'user']
            ]
        ];
        $response = new JsonResponse($data);

        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);

        $this->assertSame($data, $decoded);
    }

    public function testEncodesEmptyArray(): void
    {
        $response = new JsonResponse([]);

        $body = (string) $response->getBody();
        $this->assertSame('[]', $body);
    }
}
