# Chapter 7: HTTP Client (PSR-18)

> Part of the Larafony Framework - A modern PHP 8.5 framework built from scratch
>
> 📚 Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)

## Overview

Chapter 7 implements a PSR-18 HTTP Client for making outbound HTTP requests using PHP's native `CurlHandle`. This component enables applications to consume external APIs, webhooks, and microservices with a clean, testable interface.

The implementation provides two client types: **CurlHttpClient** for production with real network requests, and **MockHttpClient** for testing without network calls. Both implement PSR-18 ClientInterface, ensuring interchangeability and enabling dependency injection.

The architecture uses helper classes following SOLID principles: **CurlWrapper** for CurlHandle abstraction, **CurlOptionsBuilder** for CURLOPT conversion, **CurlHandleExecutor** for execution and response parsing, **ResponseParser** for PSR-7 response creation, and **HttpClientConfig** DTO for configuration. Comprehensive exception hierarchy maps HTTP errors to specific exception types (BadRequestError, UnauthorizedError, NotFoundError, etc.) and network errors (ConnectionError, DnsError, TimeoutError).

## Key Components

### HTTP Clients

- **CurlHttpClient** - PSR-18 production client using PHP CurlHandle for real HTTP requests (helpers: CurlWrapper, CurlOptionsBuilder, CurlHandleExecutor)
- **MockHttpClient** - PSR-18 test double returning predefined responses without network calls
- **HttpClientFactory** - Factory creating clients from configuration

### Configuration and Helpers

- **HttpClientConfig** - Immutable DTO for client configuration (timeout, SSL, proxy, redirects, etc.)
- **CurlOptionsBuilder** - Converts HttpClientConfig to CURLOPT_* array
- **CurlHandleExecutor** - Executes CurlHandle and creates PSR-7 response (uses ResponseParser, ResponseHeadersParser, StatusLineParser)
- **CurlWrapper** - Thin abstraction over PHP CurlHandle for testability

### Exceptions

- **ClientError** - Base exception for HTTP client errors
- **ConnectionError, DnsError, TimeoutError** - Network-level errors
- **BadRequestError (400), UnauthorizedError (401), ForbiddenError (403), NotFoundError (404), etc.** - HTTP status code errors

## PSR Standards Implemented

- **PSR-18**: HTTP Client - Full ClientInterface implementation (sendRequest method)
- **PSR-7**: HTTP Messages - Accepts RequestInterface, returns ResponseInterface
- **PSR-17**: HTTP Factories - Uses factories for creating responses
- **Type Safety**: Strict types with HttpClientConfig DTO and backed enums

## New Attributes

This chapter doesn't introduce new PHP attributes, but uses PHP 8.5 features:

- Readonly properties in HttpClientConfig DTO
- Constructor property promotion throughout
- Named arguments for clarity in configuration
- Match expressions for status code to exception mapping

## Usage Examples

### Basic Example

```php
<?php

use Larafony\Framework\Http\Client\CurlHttpClient;
use Larafony\Framework\Http\Factories\RequestFactory;

$client = new CurlHttpClient();
$requestFactory = new RequestFactory();

// Simple GET request
$request = $requestFactory->createRequest('GET', 'https://api.github.com/users/octocat');
$response = $client->sendRequest($request);

echo $response->getStatusCode(); // 200
echo $response->getBody(); // JSON response
```

### Advanced Example

```php
<?php

use Larafony\Framework\Http\Client\CurlHttpClient;
use Larafony\Framework\Http\Client\Config\HttpClientConfig;

// Custom configuration
$config = new HttpClientConfig(
    timeout: 30,
    followRedirects: true,
    maxRedirects: 5,
    verifyPeer: true,
    verifyHost: true,
);

$client = new CurlHttpClient($config);

// POST request with JSON
$request = $requestFactory->createRequest('POST', 'https://api.example.com/users')
    ->withHeader('Content-Type', 'application/json')
    ->withBody($streamFactory->createStream(json_encode([
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ])));

try {
    $response = $client->sendRequest($request);
    $data = json_decode($response->getBody(), true);
} catch (ClientError $e) {
    // Handle client errors
}
```

### Mock Client for Testing

```php
<?php

use Larafony\Framework\Http\Client\MockHttpClient;
use Larafony\Framework\Http\Factories\ResponseFactory;

// Create mock with predefined response
$mockResponse = (new ResponseFactory())
    ->createResponse(200)
    ->withJson(['id' => 1, 'name' => 'Test User']);

$client = new MockHttpClient($mockResponse);

// Test your code without network calls
$service = new UserService($client);
$user = $service->fetchUser(1); // Uses mock, no network request
```

## Comparison with Other Frameworks

| Feature | Larafony | Laravel | Symfony |
|---------|----------|---------|---------|
| **PSR-18 Compliance** | Native PSR-18 implementation | Via symfony/http-client | symfony/http-client |
| **HTTP Client** | CurlHttpClient (native CurlHandle) | Guzzle wrapper | Symfony HttpClient |
| **Testing** | MockHttpClient (PSR-18) | Http::fake() | MockHttpClient |
| **Configuration** | HttpClientConfig DTO | Array-based config | HttpClientInterface options |
| **Dependencies** | Zero (native CurlHandle) | guzzlehttp/guzzle | symfony/http-client |
| **Exception Hierarchy** | Comprehensive (ConnectionError, DnsError, etc.) | Guzzle exceptions | Symfony exceptions |

**Key Differences:**

- **PSR-18 Native:** Larafony implements PSR-18 directly. Laravel wraps Guzzle. Symfony provides PSR-18 implementation.

- **Zero Dependencies:** Larafony uses PHP's native `CurlHandle` (PHP 8+), no external packages. Laravel requires Guzzle. Symfony requires symfony/http-client.

- **DTO Configuration:** Larafony uses HttpClientConfig immutable DTO. Laravel and Symfony use arrays.

- **Mock Client:** Larafony's MockHttpClient implements same PSR-18 interface. Laravel uses Http facade mocking. Symfony has MockHttpClient.

---

📚 **Learn More:** This implementation is explained in detail with step-by-step tutorials, tests, and best practices at [masterphp.eu](https://masterphp.eu)
