<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Http\Client;

use Larafony\Framework\Http\Client\CurlHttpClient;
use Larafony\Framework\Http\Factories\UriFactory;
use Larafony\Framework\Http\Request;
use PHPUnit\Framework\Attributes\Group;

use PHPUnit\Framework\TestCase;

/**
 * Integration testS for CurlHttpClient with real HTTP requests.
 *
 * These testS make real network requests to example.com.
 * Run with: php8.5 vendor/bin/phpunit --group=integration
 *
 * Note: These are basic testS. httpbin.org is currently unavailable (503).
 */
#[Group('integration')]
final class CurlHttpClientIntegrationTest extends TestCase
{
    private CurlHttpClient $client;
    private UriFactory $uriFactory;

    protected function setUp(): void
    {
        $this->client = new CurlHttpClient();
        $this->uriFactory = new UriFactory();
    }

    
    public function testCanSendGetRequestToExampleCom(): void
    {
        $request = new Request('GET', $this->uriFactory->createUri('https://example.com'));

        $response = $this->client->sendRequest($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));

        $body = (string) $response->getBody();
        $this->assertStringContainsString('Example Domain', $body);
    }

    
    public function testCanSendGetRequestToJsonplaceholder(): void
    {
        $request = new Request('GET', $this->uriFactory->createUri('https://jsonplaceholder.typicode.com/todos/1'));

        $response = $this->client->sendRequest($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $body = (string) $response->getBody();
        $json = json_decode($body, true);

        $this->assertIsArray($json);
        $this->assertArrayHasKey('id', $json);
        $this->assertArrayHasKey('title', $json);
    }

    
    public function testReturnsResponseFor404(): void
    {
        $request = new Request('GET', $this->uriFactory->createUri('https://jsonplaceholder.typicode.com/posts/9999999'));

        $response = $this->client->sendRequest($request);

        // JSONPlaceholder returns 404 for non-existent resources
        $this->assertSame(404, $response->getStatusCode());
    }
}
