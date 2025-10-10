# Chapter 7: PSR-18 HTTP Client

This chapter implements a PSR-18 compliant HTTP client for making external HTTP requests, with full support for testing and mocking.

## Table of Contents

- [Overview](#overview)
- [Core Components](#core-components)
- [HTTP Client Configuration](#http-client-configuration)
- [Making Requests](#making-requests)
- [Error Handling](#error-handling)
- [Testing with Mocks](#testing-with-mocks)
- [Factory Pattern](#factory-pattern)
- [Complete Examples](#complete-examples)

## Overview

Larafony's HTTP Client provides:

- **PSR-18 compliant** - Full implementation of HTTP client interface
- **PSR-7 integration** - Uses PSR-7 Request/Response objects
- **CurlHandle-based** - Native PHP CURL implementation, no external dependencies
- **Configurable** - Clean DTO-based configuration
- **Testable** - Built-in mocking support with MockHttpClient
- **Comprehensive error handling** - Specific exceptions for different failure scenarios
- **Factory pattern** - Easy switching between real and mock implementations

## Core Components

### 1. CurlHttpClient

The production HTTP client that makes real network requests using PHP's CurlHandle.

```php
use Larafony\Framework\Http\Client\CurlHttpClient;
use Larafony\Framework\Http\Client\Config\HttpClientConfig;
use Larafony\Framework\Http\Factories\RequestFactory;

// Create with default config
$client = new CurlHttpClient();

// Create with custom timeout
$client = new CurlHttpClient(HttpClientConfig::withTimeout(60));

// Make a request
$request = new RequestFactory()->createRequest('GET', 'https://example.com/');
$response = $client->sendRequest($request);

echo $response->getStatusCode(); // 200
echo $response->getBody(); 
```

**Features:**
- Based on native PHP CurlHandle
- Full PSR-18 compliance
- Supports all HTTP methods (GET, POST, PUT, DELETE, PATCH, etc.)
- Handles timeouts, redirects, SSL/TLS verification
- Proxy support
- HTTP/2 support by default

### 2. MockHttpClient

A test double for the HTTP client that doesn't make real network requests.

```php
use Larafony\Framework\Http\Client\MockHttpClient;
use Larafony\Framework\Http\Client\Mock\CallbackMockHandler;
use Larafony\Framework\Http\Factories\ResponseFactory;
use \Larafony\Framework\Web\Application;

// Create mock handler
$mockHandler = new CallbackMockHandler(function ($request) {
    return Application::instance()->get(ResponseFactory::class)->createResponse(200)
        ->withJson(['id' => 1, 'name' => 'John Doe']);
});

// Create mock client
$client = new MockHttpClient($mockHandler);

// Make request (no network call)
$response = $client->sendRequest($request);

// Assert in tests
$this->assertSame(200, $response->getStatusCode());
$this->assertTrue($client->hasRequests());
$this->assertSame('GET', $client->getLastRequest()->getMethod());
```

**Testing Features:**
- Request history tracking
- Callback-based response generation
- No real network calls
- Similar to FrozenClock pattern from Chapter 3

## HTTP Client Configuration

The `HttpClientConfig` DTO provides type-safe configuration without remembering CURL constants.

### Default Configuration

```php
use Larafony\Framework\Http\Client\Config\HttpClientConfig;

$config = new HttpClientConfig(
    timeout: 30,              // Request timeout in seconds
    connectTimeout: 10,       // Connection timeout in seconds
    followRedirects: true,    // Follow HTTP redirects
    maxRedirects: 10,         // Maximum number of redirects
    verifyPeer: true,         // Verify SSL peer certificate
    verifyHost: true,         // Verify SSL host
    proxy: null,              // Proxy server (e.g., 'proxy.local:8080')
    proxyAuth: null,          // Proxy authentication (e.g., 'user:pass')
    httpVersion: CURL_HTTP_VERSION_2_0, // HTTP version (HTTP/2 by default)
);
```

### Configuration Presets

```php
// Custom timeout
$config = HttpClientConfig::withTimeout(60);

// Local development (no SSL verification)
$config = HttpClientConfig::insecure();

// With proxy
$config = HttpClientConfig::withProxy('proxy.local:8080', 'user:pass');

// Don't follow redirects
$config = HttpClientConfig::noRedirects();

// HTTP/1.1 only (for compatibility)
$config = HttpClientConfig::http11();
```

### Example: API Client with Custom Config

```php
use Larafony\Framework\Http\Client\CurlHttpClient;
use Larafony\Framework\Http\Client\Config\HttpClientConfig;
use Larafony\Framework\Http\Factories\RequestFactory;

class GitHubApiClient
{
    private CurlHttpClient $client;

    public function __construct(
        private readonly RequestFactory $requestFactory,
    ) {
        // GitHub API requires User-Agent and has rate limits
        $config = HttpClientConfig::withTimeout(30);
        $this->client = new CurlHttpClient($config);
    }

    public function getUser(string $username): array
    {
        $request = $this->requestFactory
            ->createRequest('GET', "https://api.github.com/users/{$username}")
            ->withHeader('User-Agent', 'Larafony-App/1.0')
            ->withHeader('Accept', 'application/vnd.github.v3+json');

        $response = $this->client->sendRequest($request);
        return json_decode((string) $response->getBody(), true);
    }
}
```

## Making Requests

### Basic GET Request

```php
use Larafony\Framework\Http\Factories\RequestFactory;
use Larafony\Framework\Http\Client\HttpClientFactory;
use \Larafony\Framework\Web\Application;

$factory = Application::instance()->get(RequestFactory::class);
$client = HttpClientFactory::instance();

// Simple GET
$request = $factory->createRequest('GET', 'https://example.com');
$response = $client->sendRequest($request);

echo $response->getStatusCode(); // 200
$data = json_decode((string) $response->getBody(), true);
```

### POST Request with JSON

```php
use Larafony\Framework\Http\Factories\RequestFactory;
use Larafony\Framework\Http\Factories\StreamFactory;
use Larafony\Framework\Web\Application;

$app = Application::instance();
$requestFactory = $app->get(RequestFactory::class);
$streamFactory = $app->get(StreamFactory::class);

$data = ['name' => 'John Doe', 'email' => 'john@example.com'];
$body = $streamFactory->createStream(json_encode($data));

$request = $requestFactory
    ->createRequest('POST', 'https://api.example.com/users')
    ->withHeader('Content-Type', 'application/json')
    ->withHeader('Accept', 'application/json')
    ->withBody($body);

$response = HttpClientFactory::sendRequest($request);
```

### Request with Headers

```php
$request = $factory
    ->createRequest('GET', 'https://api.example.com/protected')
    ->withHeader('Authorization', 'Bearer ' . $token)
    ->withHeader('Accept', 'application/json')
    ->withHeader('User-Agent', 'Larafony/1.0');

$response = $client->sendRequest($request);
```

### PUT/PATCH Request

```php
$data = ['name' => 'Jane Doe'];
$body = $streamFactory->createStream(json_encode($data));

$request = $factory
    ->createRequest('PUT', 'https://api.example.com/users/123')
    ->withHeader('Content-Type', 'application/json')
    ->withBody($body);

$response = $client->sendRequest($request);
```

### DELETE Request

```php
$request = $factory->createRequest('DELETE', 'https://api.example.com/users/123');
$response = $client->sendRequest($request);

if ($response->getStatusCode() === 204) {
    echo 'User deleted successfully';
}
```

## Error Handling

The HTTP Client provides comprehensive exception hierarchy based on PSR-18.

### Exception Hierarchy

```
HttpClientError (base, implements ClientExceptionInterface)
├── NetworkError (implements NetworkExceptionInterface)
│   ├── ConnectionError - Connection failures
│   ├── DnsError - DNS resolution failures
│   └── TimeoutError - Network timeouts
├── RequestError (implements RequestExceptionInterface)
│   ├── InvalidRequestError - Malformed requests
│   ├── TooManyRedirectsError - Redirect limit exceeded
│   └── TimeoutError - Request timeouts
└── HttpError - HTTP-level errors
    ├── ClientError (4xx responses)
    │   ├── BadRequestError (400)
    │   ├── UnauthorizedError (401)
    │   ├── ForbiddenError (403)
    │   └── NotFoundError (404)
    └── ServerError (5xx responses)
        ├── InternalServerError (500)
        ├── BadGatewayError (502)
        └── ServiceUnavailableError (503)
```

### Catching Specific Exceptions

```php
use Larafony\Framework\Http\Client\Exceptions\NetworkError;
use Larafony\Framework\Http\Client\Exceptions\TimeoutError;
use Larafony\Framework\Http\Client\Exceptions\NotFoundError;
use Larafony\Framework\Http\Client\Exceptions\ServerError;

try {
    $response = $client->sendRequest($request);
} catch (TimeoutError $e) {
    // Request timed out
    logger()->error("Request timed out: {$e->getUri()}");
} catch (NotFoundError $e) {
    // 404 Not Found
    return null;
} catch (ServerError $e) {
    // 500+ server errors
    logger()->critical("Server error: " . $e->getMessage());
} catch (NetworkError $e) {
    // Network issues (DNS, connection, SSL)
    logger()->warning("Network error for {$e->getMethod()} {$e->getUri()}");
}
```

### PSR-18 Exception Interfaces

All exceptions implement PSR-18 interfaces:

```php
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Client\RequestExceptionInterface;

try {
    $response = $client->sendRequest($request);
} catch (NetworkExceptionInterface $e) {
    // Network-level errors (connection, DNS, SSL)
    // Note: getRequest() is not available, use getMethod() and getUri()
    echo "Network error: {$e->getMethod()} {$e->getUri()}";
} catch (RequestExceptionInterface $e) {
    // Request-level errors (invalid request, timeout, too many redirects)
    echo "Request error: {$e->getMessage()}";
} catch (ClientExceptionInterface $e) {
    // Any client error
    echo "HTTP client error: {$e->getMessage()}";
}
```

**Important Note:** `NetworkError` and `RequestError` store only request metadata (method, URI) instead of the full Request object to avoid memory leaks and serialization issues. Use `getMethod()` and `getUri()` methods instead of `getRequest()`.

## Testing with Mocks

### Basic Mocking with Callback

```php
use Larafony\Framework\Http\Client\MockHttpClient;
use Larafony\Framework\Http\Client\Mock\CallbackMockHandler;
use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Http\Factories\RequestFactory;
use Larafony\Framework\Web\Application;

// In your test - get factories from container
$app = Application::instance();
$requestFactory = $app->get(RequestFactory::class);
$responseFactory = $app->get(ResponseFactory::class);

$mockHandler = new CallbackMockHandler(function ($request) use ($responseFactory) {
    return $responseFactory->createResponse(200)
        ->withJson(['success' => true]);
});

$client = new MockHttpClient($mockHandler);

// Make request (no network call)
$request = $requestFactory->createRequest('GET', 'https://api.example.com/test');
$response = $client->sendRequest($request);

// Assertions
$this->assertSame(200, $response->getStatusCode());
$this->assertTrue($client->hasRequests());
$this->assertSame('GET', $client->getLastRequest()->getMethod());
$this->assertSame('https://api.example.com/test', (string) $client->getLastRequest()->getUri());
```

### Dynamic Responses Based on Request

```php
use Psr\Http\Message\RequestInterface;

$app = Application::instance();
$responseFactory = $app->get(ResponseFactory::class);

$mockHandler = new CallbackMockHandler(function (RequestInterface $request) use ($responseFactory) {
    // Different responses based on HTTP method
    if ($request->getMethod() === 'POST') {
        return $responseFactory->createResponse(201)
            ->withJson(['created' => true, 'id' => 123]);
    }

    // Different responses based on URL
    if (str_contains((string) $request->getUri(), '/users/1')) {
        return $responseFactory->createResponse(200)
            ->withJson(['id' => 1, 'name' => 'John Doe']);
    }

    // Different responses based on headers
    if ($request->hasHeader('Authorization')) {
        return $responseFactory->createResponse(200)
            ->withJson(['authenticated' => true]);
    }

    // Default response
    return $responseFactory->createResponse(404)
        ->withJson(['error' => 'Not found']);
});
```

### Asserting Request Details

```php
$client = new MockHttpClient($mockHandler);

// Make requests
$client->sendRequest($request1);
$client->sendRequest($request2);
$client->sendRequest($request3);

// Assert request count
$this->assertCount(3, $client->getRequestHistory());

// Assert last request
$lastRequest = $client->getLastRequest();
$this->assertSame('POST', $lastRequest->getMethod());
$this->assertSame('application/json', $lastRequest->getHeaderLine('Content-Type'));

// Assert request body
$body = json_decode((string) $lastRequest->getBody(), true);
$this->assertSame('John Doe', $body['name']);

// Reset history for next test
$client->resetHistory();
$this->assertFalse($client->hasRequests());
```

## Factory Pattern

The `HttpClientFactory` provides a global singleton pattern for easy testing, similar to `ClockFactory` from Chapter 3.

### Production Usage

```php
use Larafony\Framework\Http\Client\HttpClientFactory;

// Get client instance (creates CurlHttpClient by default)
$client = HttpClientFactory::instance();

// Or use static method directly
$response = HttpClientFactory::sendRequest($request);
```

### Testing with Factory

```php
use Larafony\Framework\Http\Client\HttpClientFactory;
use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Web\Application;

class UserServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $app = Application::instance();
        $responseFactory = $app->get(ResponseFactory::class);

        // Fake HTTP client for all tests
        HttpClientFactory::fake(function ($request) use ($responseFactory) {
            return $responseFactory->createResponse(200)
                ->withJson(['id' => 1, 'name' => 'Test User']);
        });
    }

    protected function tearDown(): void
    {
        // Reset to real client
        HttpClientFactory::reset();
        parent::tearDown();
    }

    public function test_fetches_user(): void
    {
        $service = new UserService();
        $user = $service->getUser(1);

        $this->assertSame('Test User', $user['name']);
    }
}
```

### Factory Methods

```php
// Get current instance (creates CurlHttpClient if not set)
HttpClientFactory::instance();

// Set custom client instance
HttpClientFactory::withInstance(new CurlHttpClient($config));

// Create mock client with callback
$mockClient = HttpClientFactory::fake(fn($req) => $response);

// Create mock client with custom handler
$mockClient = HttpClientFactory::fakeWithHandler($customHandler);

// Reset to default CurlHttpClient
HttpClientFactory::reset();

// Send request using current instance
HttpClientFactory::sendRequest($request);
```

## Complete Examples

### Example 1: API Client Service

```php
use Larafony\Framework\Http\Client\HttpClientFactory;
use Larafony\Framework\Http\Factories\RequestFactory;
use Larafony\Framework\Http\Client\Exceptions\NotFoundError;
use Larafony\Framework\Http\Client\Exceptions\ServerError;

class GitHubService
{
    public function __construct(
        private readonly RequestFactory $requestFactory,
    ) {
    }

    public function getUser(string $username): ?array
    {
        $request = $this->requestFactory
            ->createRequest('GET', "https://api.github.com/users/{$username}")
            ->withHeader('User-Agent', 'Larafony/1.0')
            ->withHeader('Accept', 'application/vnd.github.v3+json');

        try {
            $response = HttpClientFactory::sendRequest($request);
            return json_decode((string) $response->getBody(), true);
        } catch (NotFoundError) {
            return null;
        } catch (ServerError $e) {
            logger()->error("GitHub API error: {$e->getMessage()}");
            throw $e;
        }
    }

    public function createRepository(string $name, string $token): array
    {
        $body = json_encode(['name' => $name, 'private' => false]);

        $request = $this->requestFactory
            ->createRequest('POST', 'https://api.github.com/user/repos')
            ->withHeader('Authorization', "Bearer {$token}")
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/vnd.github.v3+json')
            ->withBody((new StreamFactory())->createStream($body));

        $response = HttpClientFactory::sendRequest($request);
        return json_decode((string) $response->getBody(), true);
    }
}
```

### Example 2: Testing the API Client

```php
use Larafony\Framework\Tests\TestCase;
use Larafony\Framework\Http\Client\HttpClientFactory;
use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Http\Factories\RequestFactory;
use Larafony\Framework\Web\Application;

class GitHubServiceTest extends TestCase
{
    private GitHubService $service;
    private ResponseFactory $responseFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $app = Application::instance();
        $requestFactory = $app->get(RequestFactory::class);
        $this->responseFactory = $app->get(ResponseFactory::class);

        $this->service = new GitHubService($requestFactory);
    }

    protected function tearDown(): void
    {
        HttpClientFactory::reset();
        parent::tearDown();
    }

    public function test_gets_user_successfully(): void
    {
        HttpClientFactory::fake(function ($request) {
            $this->assertSame('GET', $request->getMethod());
            $this->assertStringContainsString('octocat', (string) $request->getUri());

            return $this->responseFactory->createResponse(200)
                ->withJson([
                    'login' => 'octocat',
                    'id' => 1,
                    'name' => 'The Octocat',
                ]);
        });

        $user = $this->service->getUser('octocat');

        $this->assertSame('octocat', $user['login']);
        $this->assertSame('The Octocat', $user['name']);
    }

    public function test_returns_null_when_user_not_found(): void
    {
        HttpClientFactory::fake(function ($request) {
            return $this->responseFactory->createResponse(404)
                ->withJson(['message' => 'Not Found']);
        });

        $user = $this->service->getUser('nonexistent');

        $this->assertNull($user);
    }

    public function test_creates_repository(): void
    {
        $mockClient = HttpClientFactory::fake(function ($request) {
            $this->assertSame('POST', $request->getMethod());
            $this->assertSame('Bearer my-token', $request->getHeaderLine('Authorization'));

            $body = json_decode((string) $request->getBody(), true);
            $this->assertSame('my-repo', $body['name']);

            return $this->responseFactory->createResponse(201)
                ->withJson([
                    'id' => 123,
                    'name' => 'my-repo',
                    'private' => false,
                ]);
        });

        $repo = $this->service->createRepository('my-repo', 'my-token');

        $this->assertSame(123, $repo['id']);
        $this->assertSame('my-repo', $repo['name']);

        // Assert request was made
        $this->assertTrue($mockClient->hasRequests());
        $this->assertCount(1, $mockClient->getRequestHistory());
    }
}
```

### Example 3: Webhook Handler with Retry Logic

```php
use Larafony\Framework\Http\Client\CurlHttpClient;
use Larafony\Framework\Http\Client\Config\HttpClientConfig;
use Larafony\Framework\Http\Client\Exceptions\NetworkError;
use Larafony\Framework\Http\Client\Exceptions\TimeoutError;
use Larafony\Framework\Http\Factories\RequestFactory;
use Larafony\Framework\Http\Factories\StreamFactory;

class WebhookDispatcher
{
    private CurlHttpClient $client;

    public function __construct(
        private readonly RequestFactory $requestFactory,
        private readonly StreamFactory $streamFactory,
    ) {
        // Shorter timeout for webhooks
        $config = HttpClientConfig::withTimeout(10);
        $this->client = new CurlHttpClient($config);
    }

    public function dispatch(string $url, array $payload, int $maxRetries = 3): bool
    {
        $body = json_encode($payload);

        $request = $this->requestFactory
            ->createRequest('POST', $url)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('User-Agent', 'Larafony-Webhook/1.0')
            ->withBody($this->streamFactory->createStream($body));

        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                $response = $this->client->sendRequest($request);

                // Success if 2xx response
                if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                    return true;
                }

                // Log non-2xx responses
                logger()->warning("Webhook returned {$response->getStatusCode()}: {$url}");

            } catch (TimeoutError $e) {
                logger()->warning("Webhook timeout (attempt {$attempt}): {$url}");
            } catch (NetworkError $e) {
                logger()->warning("Webhook network error (attempt {$attempt}): {$e->getMessage()}");
            }

            $attempt++;

            // Exponential backoff
            if ($attempt < $maxRetries) {
                sleep(2 ** $attempt);
            }
        }

        return false;
    }
}
```

## Key Differences from Other HTTP Clients

| Feature | Guzzle | Symfony HTTP Client | **Larafony HTTP Client** |
|---------|--------|---------------------|--------------------------|
| PSR-18 Compliance | ✓ | ✓ | **✓** |
| PSR-7 Integration | ✓ | ✗ (custom) | **✓** |
| External Dependencies | Many | Symfony components | **✗ (PSR only)** |
| Native PHP CURL | ✓ | ✓ | **✓** |
| DTO Configuration | ✗ | ✗ | **✓ (HttpClientConfig)** |
| Built-in Mocking | ✗ (separate package) | ✓ | **✓ (MockHttpClient)** |
| Request History | ✗ | ✓ | **✓** |
| Factory Pattern | ✗ | ✗ | **✓ (HttpClientFactory)** |
| Exception Hierarchy | Complex | Symfony-specific | **✓ (PSR-18 + specific errors)** |

**Notes:**
- **Zero External Dependencies** - Only PSR packages required, no bloat
- **DTO Configuration** - Type-safe config without remembering CURL constants
- **Testing-First Design** - MockHttpClient built-in, not an afterthought
- **Factory Pattern** - Easy global mocking like ClockFactory from Chapter 3
- **Specific Exceptions** - Fine-grained error handling (NotFoundError, TimeoutError, etc.)

## Related Documentation

- [Framework README](../../README.md)
- [Chapter 5: PSR-7 HTTP Foundation](./chapter5.md)
- [Chapter 6: Web Application & Routing](./chapter6.md)

## References

- [PSR-18: HTTP Client](https://www.php-fig.org/psr/psr-18/)
- [PSR-7: HTTP Message Interface](https://www.php-fig.org/psr/psr-7/)
- [PHP CURL Documentation](https://www.php.net/manual/en/book.curl.php)

## What's Next?

**Chapter 8** will introduce environment variables and configuration management, allowing you to store API keys, database credentials, and other settings securely.
