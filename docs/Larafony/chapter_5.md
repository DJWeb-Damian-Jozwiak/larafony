# Chapter 5: HTTP Messages (PSR-7 & PSR-17)

> Part of the Larafony Framework - A modern PHP 8.5 framework built from scratch
>
> 📚 Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)

## Overview

Chapter 5 implements a complete HTTP message layer following PSR-7 (HTTP Message Interface) and PSR-17 (HTTP Factories) standards. This component provides immutable representations of HTTP requests, responses, streams, and URIs, forming the foundation for web applications in the Larafony framework.

The implementation focuses on strict PSR compliance while leveraging PHP 8.5's modern features. All HTTP messages are immutable value objects - every modification returns a new instance rather than changing the existing one. This functional approach eliminates side effects, makes code easier to reason about, and enables safe concurrent processing.

The architecture introduces **Request/Response** as PSR-7 HTTP message implementations, **ServerRequest** for server-side HTTP with superglobal parsing, **Stream** for efficient body content handling with resource management, **UriManager** for parsing and manipulating URIs, **PSR-17 Factories** for creating all HTTP objects, and **Application** class as the web application foundation with service provider integration and response emission. All components use decorator patterns for uploaded files, helper classes for complex operations, and extensive validation to ensure data integrity.

## Key Components

### HTTP Messages (PSR-7)

- **Request** - HTTP request implementation with method, URI, headers, and body (implements PSR-7 RequestInterface)
- **Response** - HTTP response with status code, reason phrase, headers, and body (implements PSR-7 ResponseInterface, helper: StatusCodeFactory for reason phrases)
- **ServerRequest** - Server-side HTTP request with superglobals: $_SERVER, $_COOKIE, $_GET, $_POST, $_FILES (implements PSR-7 ServerRequestInterface, helpers: QueryParamsManager, ParsedBodyManager, UploadedFilesManager, AttributesManager for managing request data)
- **Message** - Base class for Request/Response with protocol version, headers, and body (helper: HeaderManager for header manipulation)

### HTTP Factories (PSR-17)

- **RequestFactory, ResponseFactory, ServerRequestFactory** - PSR-17 factories for creating HTTP message objects from superglobals or raw data
- **StreamFactory, UploadedFileFactory, UriFactory** - PSR-17 factories for creating streams, uploaded files, and URI objects (helpers: StreamMetaDataFactory for stream metadata)

### Supporting Components

- **Stream** - PSR-7 stream implementation with resource handling, seeking, reading, and writing (helpers: StreamWrapper for resource management, StreamContentManager, StreamSize, StreamMetaData)
- **UploadedFile** - PSR-7 uploaded file representation with error validation and move operations (decorators: ErrorValidatorDecorator, MoveStatusDecorator, PathValidatorDecorator for validation chain, handlers: FileMoveHandler, StreamMoveHandler for different move strategies)
- **UriManager** - PSR-7 URI implementation with parsing and manipulation (helpers: Authority for user/host/port, Query for query string, Scheme for protocol)
- **JsonResponse** - Convenience response class for JSON APIs with automatic content-type headers

### Web Application

- **Application** - Web application container extending Container with service provider registration and PSR-7 response emission
- **HttpServiceProvider** - Service provider registering all HTTP factories and PSR-7 interfaces into the container

## PSR Standards Implemented

- **PSR-7**: HTTP Message Interface - Full implementation of RequestInterface, ResponseInterface, ServerRequestInterface, StreamInterface, UriInterface, UploadedFileInterface, MessageInterface
- **PSR-17**: HTTP Factories - Complete factory implementations: RequestFactoryInterface, ResponseFactoryInterface, ServerRequestFactoryInterface, StreamFactoryInterface, UploadedFileFactoryInterface, UriFactoryInterface
- **PSR-11**: Container Interface - Application class extends Container for dependency injection
- **Immutability**: All HTTP messages are immutable value objects (withHeader(), withBody(), etc. return new instances)
- **Type Safety**: Strict typing with `declare(strict_types=1)` throughout all components

## New Attributes

This chapter doesn't introduce new PHP attributes, but extensively uses PHP 8.5 features:

- `private(set)` property hooks in Application class for asymmetric visibility
- `readonly` properties throughout for immutability enforcement
- Constructor property promotion in all classes
- `match` expressions for HTTP status code to reason phrase mapping
- Named arguments for clarity (e.g., `parent::__construct(protocolVersion: ...)`)
- First-class callables in service provider array_walk

## Usage Examples

### Basic Example - Creating HTTP Response

```php
<?php

use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Http\Factories\StreamFactory;

$responseFactory = new ResponseFactory();
$streamFactory = new StreamFactory();

// Create simple response
$response = $responseFactory->createResponse(200)
    ->withHeader('Content-Type', 'text/html')
    ->withBody($streamFactory->createStream('<h1>Hello World</h1>'));

echo $response->getStatusCode(); // 200
echo $response->getReasonPhrase(); // OK
echo $response->getHeaderLine('Content-Type'); // text/html
echo $response->getBody(); // <h1>Hello World</h1>
```

### Advanced Example - Server Request Handling

```php
<?php

use Larafony\Framework\Http\Factories\ServerRequestFactory;
use Larafony\Framework\Http\Factories\ResponseFactory;

// Create PSR-7 request from superglobals
$factory = new ServerRequestFactory();
$request = $factory->createServerRequest(
    method: $_SERVER['REQUEST_METHOD'],
    uri: $_SERVER['REQUEST_URI'],
    serverParams: $_SERVER
);

// Access request data
$method = $request->getMethod(); // GET, POST, etc.
$path = $request->getUri()->getPath(); // /users/123
$queryParams = $request->getQueryParams(); // $_GET data
$parsedBody = $request->getParsedBody(); // $_POST data or JSON
$uploadedFiles = $request->getUploadedFiles(); // $_FILES data
$cookies = $request->getCookieParams(); // $_COOKIE data

// Add custom attributes (for middleware, routing params, etc.)
$request = $request->withAttribute('user_id', 123)
                    ->withAttribute('role', 'admin');

$userId = $request->getAttribute('user_id'); // 123

// Create response
$responseFactory = new ResponseFactory();
$response = $responseFactory->createResponse(200)
    ->withHeader('Content-Type', 'application/json')
    ->withJson(['user_id' => $userId, 'status' => 'success']);
```

### JSON Response Example

```php
<?php

use Larafony\Framework\Http\JsonResponse;

// Automatic JSON encoding with content-type header
$data = [
    'status' => 'success',
    'data' => ['id' => 1, 'name' => 'John Doe'],
    'meta' => ['timestamp' => time()]
];

$response = new JsonResponse(data: $data, status: 200);

// Headers automatically set:
// Content-Type: application/json
// Body: {"status":"success","data":{"id":1,"name":"John Doe"},"meta":{"timestamp":...}}
```

### Application Bootstrap Example

```php
<?php

use Larafony\Framework\Web\Application;
use Larafony\Framework\Http\ServiceProviders\HttpServiceProvider;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

// Create application instance
$app = Application::instance(__DIR__ . '/..');

// Register service providers
$app->withServiceProviders([
    HttpServiceProvider::class,
]);

// Get PSR-7 request from container (autowired)
$request = $app->get(ServerRequestInterface::class);

// Process request (routing, controller, etc.)
$response = processRequest($request); // Returns ResponseInterface

// Emit PSR-7 response to browser
$app->emit($response);
// Outputs: HTTP status line, headers, and body
```

## Implementation Details

### Response

**Location:** `src/Larafony/Http/Response.php:13`

**Purpose:** PSR-7 HTTP response implementation with status code management and immutable headers.

**Key Methods:**
- `getStatusCode(): int` - Get HTTP status code (200, 404, 500, etc.)
- `getReasonPhrase(): string` - Get status reason phrase ("OK", "Not Found", etc.)
- `withStatus(int $code, string $reasonPhrase = ''): static` - Return new instance with status code
- `withContent(string $content): static` - Convenience method to set body content
- `withJson(mixed $data, int $flags = 0): static` - Set JSON body with automatic encoding

**Dependencies:** StatusCodeFactory for reason phrase mapping, StreamFactory for body creation

**Usage:**
```php
$response = new Response(
    statusCode: 404,
    reasonPhrase: 'Not Found' // Optional, auto-detected
);

$response = $response
    ->withHeader('Content-Type', 'application/json')
    ->withJson(['error' => 'Resource not found']);
```

### ServerRequest

**Location:** `src/Larafony/Http/ServerRequest.php:16`

**Purpose:** Complete PSR-7 server request implementation parsing PHP superglobals.

**Key Methods:**
- `getServerParams(): array` - $_SERVER data
- `getCookieParams(): array` - $_COOKIE data
- `getQueryParams(): array` - $_GET data (parsed from URI)
- `getParsedBody(): mixed` - $_POST data or JSON decoded body
- `getUploadedFiles(): array` - $_FILES data as PSR-7 UploadedFileInterface[]
- `getAttributes(): array` - Custom attributes (middleware data, route params, etc.)
- `getAttribute(string $name, mixed $default = null): mixed` - Get single attribute
- `withAttribute(string $name, mixed $value): static` - Add attribute (returns new instance)

**Dependencies:** Multiple helper managers for each data type (QueryParamsManager, ParsedBodyManager, UploadedFilesManager, AttributesManager)

**Immutability:**
```php
$request = new ServerRequest();

// withAttribute returns NEW instance
$newRequest = $request->withAttribute('user_id', 123);

echo $request->getAttribute('user_id'); // null (original unchanged)
echo $newRequest->getAttribute('user_id'); // 123 (new instance)
```

### ServerRequestFactory

**Location:** `src/Larafony/Http/Factories/ServerRequestFactory.php:17`

**Purpose:** PSR-17 factory creating ServerRequest from superglobals or custom data.

**Key Methods:**
- `createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface` - PSR-17 method
- `fromGlobals(): ServerRequestInterface` - Create from $_SERVER, $_COOKIE, $_GET, $_POST, $_FILES

**Smart Parsing:**
- Automatically parses `Content-Type: application/json` bodies
- Normalizes uploaded files from $_FILES nested arrays
- Extracts headers from $_SERVER (HTTP_* keys)
- Parses URI from $_SERVER['REQUEST_URI']

**Usage:**
```php
$factory = new ServerRequestFactory();

// From superglobals
$request = $factory->fromGlobals();

// Custom request
$request = $factory->createServerRequest(
    'POST',
    '/api/users',
    ['REMOTE_ADDR' => '127.0.0.1']
);
```

### Application

**Location:** `src/Larafony/Web/Application.php:11`

**Purpose:** Web application foundation extending Container with service provider support and response emission.

**Key Methods:**
- `instance(?string $base_path = null): Application` - Singleton factory
- `withServiceProviders(array $serviceProviders): self` - Register and boot providers
- `emit(ResponseInterface $response): void` - Output PSR-7 response to browser

**Features:**
- **Singleton Pattern:** One application instance per request
- **Asymmetric Visibility:** `private(set) ?string $base_path` - publicly readable, privately writable
- **Service Provider Chain:** Automatically registers and boots providers in array order
- **Response Emission:** Sends HTTP status line, headers, and body to output buffer

**Usage:**
```php
$app = Application::instance(__DIR__);

$app->withServiceProviders([
    ErrorHandlerServiceProvider::class,
    HttpServiceProvider::class,
]);

$request = $app->get(ServerRequestInterface::class);
$response = handleRequest($request);
$app->emit($response); // Sends HTTP response to browser
```

### UriManager

**Location:** `src/Larafony/Http/UriManager.php:13`

**Purpose:** PSR-7 URI implementation with immutable manipulation and parsing.

**Key Methods:**
- `getScheme(): string` - http, https, etc.
- `getAuthority(): string` - user:pass@host:port
- `getUserInfo(): string` - user:password
- `getHost(): string` - domain.com
- `getPort(): ?int` - 8080 or null (standard ports)
- `getPath(): string` - /path/to/resource
- `getQuery(): string` - param1=value1&param2=value2
- `getFragment(): string` - #anchor
- `withScheme(string $scheme): static` - Return new URI with different scheme
- `withHost(string $host): static` - Return new URI with different host
- etc.

**Helpers:**
- Authority - Manages user/host/port composition
- Query - Parses and builds query strings
- Scheme - Validates and normalizes schemes

**Usage:**
```php
$uri = new UriManager('https://user:pass@example.com:8080/path?q=search#top');

echo $uri->getScheme(); // https
echo $uri->getHost(); // example.com
echo $uri->getPort(); // 8080
echo $uri->getPath(); // /path
echo $uri->getQuery(); // q=search

// Immutable changes
$newUri = $uri->withScheme('http')->withPort(80);
echo $newUri; // http://user:pass@example.com/path?q=search#top
```

### Stream

**Location:** `src/Larafony/Http/Stream.php:16`

**Purpose:** PSR-7 stream implementation wrapping PHP resources with full stream operations.

**Key Methods:**
- `read(int $length): string` - Read bytes from stream
- `write(string $string): int` - Write bytes to stream
- `seek(int $offset, int $whence = SEEK_SET): void` - Move stream pointer
- `rewind(): void` - Seek to beginning
- `isSeekable(): bool` - Check if stream supports seeking
- `isWritable(): bool` - Check if stream is writable
- `isReadable(): bool` - Check if stream is readable
- `getSize(): ?int` - Get stream size in bytes
- `tell(): int` - Get current pointer position
- `eof(): bool` - Check if at end of file
- `getContents(): string` - Read remaining content
- `getMetadata(?string $key = null): mixed` - Get stream metadata

**Resource Management:**
- Automatically closes resources in destructor
- Validates operations (no write to read-only stream)
- Handles seekable vs non-seekable streams

**Usage:**
```php
$stream = new Stream(fopen('php://temp', 'r+'));

$stream->write('Hello World');
$stream->rewind();

echo $stream->read(5); // Hello
echo $stream->getContents(); //  World
echo $stream->getSize(); // 11
```

## Comparison with Other Frameworks

| Feature | Larafony | Laravel | Symfony |
|---------|----------|---------|---------|
| **PSR-7 Compliance** | Native PSR-7 implementation | Optional via symfony/psr-http-message-bridge | Native PSR-7 via symfony/http-foundation |
| **PSR-17 Factories** | Full PSR-17 implementation | Not implemented | PSR-17 via nyholm/psr7 |
| **HTTP Foundation** | PSR-7 only | Symfony HttpFoundation (non-PSR) | HttpFoundation with PSR-7 bridge |
| **Immutability** | Fully immutable messages | Mutable Request/Response | Mutable by default, PSR-7 bridge for immutability |
| **Request Source** | ServerRequestFactory::fromGlobals() | Illuminate\Http\Request::capture() | Request::createFromGlobals() |
| **Response Emission** | Application::emit() | Built into framework kernel | HttpKernel->send() |
| **JSON Responses** | JsonResponse with withJson() | JsonResponse class | JsonResponse class |
| **File Uploads** | PSR-7 UploadedFileInterface | UploadedFile class | UploadedFile class |
| **Dependencies** | psr/http-message, psr/http-factory | symfony/http-foundation | symfony/http-foundation |

**Key Differences:**

- **PSR-7 Native:** Larafony is built entirely on PSR-7 from the ground up. Laravel uses Symfony's HttpFoundation (mutable) with optional PSR-7 bridge. Symfony uses HttpFoundation natively but offers PSR-7 bridges.

- **Immutability by Design:** Every Larafony HTTP message method (withHeader, withBody, etc.) returns a new instance. Laravel's Request/Response objects are mutable - changes modify the original object. This makes Larafony's approach more functional and thread-safe.

- **PSR-17 Factories:** Larafony implements all PSR-17 factory interfaces for creating HTTP objects. Laravel doesn't implement PSR-17. Symfony delegates to third-party packages like nyholm/psr7.

- **Zero Dependencies:** Larafony only requires PSR interfaces. Laravel requires full symfony/http-foundation package. Symfony requires its own http-foundation component.

- **Application Class:** Larafony's Application extends Container and includes `emit()` for sending PSR-7 responses. Laravel has Kernel that handles this internally. Symfony has HttpKernel with send() method.

- **Decorator Pattern:** Larafony uses decorators for UploadedFile validation (ErrorValidatorDecorator, MoveStatusDecorator). Laravel and Symfony use single classes with internal validation.

- **Stream Handling:** Larafony implements full PSR-7 StreamInterface with resource management. Laravel uses Symfony's streams. Symfony delegates to PSR-7 implementations when using bridge.

- **Helper Classes:** Larafony separates concerns with dedicated helpers (HeaderManager, QueryParamsManager, StreamWrapper). Laravel and Symfony use monolithic classes with all logic embedded.

## Real World Integration

This chapter's features are demonstrated in the demo application with a complete refactor from procedural code to PSR-7 HTTP message handling with controller-based architecture.

### Demo Application Changes

The demo application was completely refactored to use PSR-7 requests and responses through the Application class and DemoController. This demonstrates:
- Application bootstrap with service providers
- PSR-7 ServerRequest creation from superglobals
- Controller dependency injection
- PSR-7 Response creation with headers and body
- Response emission to browser

### File Structure
```
demo-app/
├── bootstrap/
│   └── web_app.php              # Application bootstrap returning configured App instance
├── public/
│   └── index.php                # Entry point using PSR-7 request/response cycle
└── src/
    └── Http/
        └── Controllers/
            └── DemoController.php # Controller handling all routes with PSR-7
```

### Implementation Example

**File: `demo-app/bootstrap/web_app.php`**

```php
<?php

declare(strict_types=1);

// Bootstrap file creating and configuring the Application instance
// This centralizes application setup and can be reused for tests

use Larafony\Framework\Web\Application;

require_once __DIR__ . '/../vendor/autoload.php';

// Create singleton Application instance with base path
// base_path is used for locating config, storage, etc.
return Application::instance(__DIR__ . '/..');
```

**What's happening here:**
1. **Centralized Bootstrap:** Instead of duplicating setup in index.php, bootstrap file creates configured Application
2. **Singleton Pattern:** Application::instance() ensures one app per request
3. **Base Path:** Application stores base path for file operations (configs, storage, etc.)
4. **Reusable:** Same bootstrap can be used in tests, CLI commands, etc.

**File: `demo-app/public/index.php`**

```php
<?php

declare(strict_types=1);

use App\Http\Controllers\DemoController;
use Larafony\Framework\ErrorHandler\ServiceProviders\ErrorHandlerServiceProvider;
use Larafony\Framework\Http\ServiceProviders\HttpServiceProvider;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @var \Larafony\Framework\Web\Application $app
 */
// Get configured Application instance from bootstrap
$app = require_once __DIR__ . '/../bootstrap/web_app.php';

// Register service providers using fluent interface
// Each provider registers services and boots them in order
$app->withServiceProviders([
    ErrorHandlerServiceProvider::class,  // Registers error handler
    HttpServiceProvider::class,           // Registers PSR-7/17 factories
]);

// Resolve PSR-7 ServerRequest from container
// HttpServiceProvider registered ServerRequestInterface binding
// Container autowires ServerRequestFactory and calls fromGlobals()
$request = $app->get(ServerRequestInterface::class);

// Extract request path for simple routing (until Chapter X: Routing)
$path = $request->getUri()->getPath();

// Resolve controller from container with dependency injection
// ResponseFactory is autowired in controller constructor
$controller = $app->get(DemoController::class);

// Route to controller method based on path
// Each controller method accepts ServerRequestInterface and returns ResponseInterface
$response = match ($path) {
    '/' => $controller->home($request),
    '/info' => $controller->info($request),
    '/error' => $controller->handleError($request),
    '/exception' => $controller->handleException($request),
    '/fatal' => $controller->handleFatal($request),
    default => $controller->handleNotFound($request),
};

// Emit PSR-7 response to browser
// Application::emit() sends HTTP status, headers, and body
$app->emit($response);
```

**What's happening here:**

1. **Application Bootstrap** (line 15): Load Application from bootstrap file (reusable setup)

2. **Service Provider Registration** (lines 18-21): Use Application::withServiceProviders() to register and boot providers
   - ErrorHandlerServiceProvider registers DetailedErrorHandler
   - HttpServiceProvider registers all PSR-7/17 factories and binds interfaces

3. **PSR-7 Request Resolution** (line 24): Get ServerRequestInterface from container
   - Container resolves ServerRequestFactory
   - Factory calls fromGlobals() to parse $_SERVER, $_GET, $_POST, $_FILES, $_COOKIE
   - Returns immutable PSR-7 ServerRequest

4. **Path Extraction** (line 28): Use PSR-7 methods to get URI path (not $_SERVER directly)

5. **Controller Resolution** (line 31): Container autowires DemoController with ResponseFactory dependency

6. **Routing with Match** (lines 33-40): Route based on path
   - All controller methods accept ServerRequestInterface
   - All controller methods return ResponseInterface
   - Type-safe PSR-7 contract throughout

7. **Response Emission** (line 43): Application::emit() outputs HTTP response
   - Sends status line: `HTTP/1.1 200 OK`
   - Sends headers: `Content-Type: text/html`
   - Sends body content

**File: `demo-app/src/Http/Controllers/DemoController.php`**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Clock\Enums\TimeFormat;
use Larafony\Framework\Clock\Enums\Timezone;
use Larafony\Framework\Http\Factories\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class DemoController
{
    // Constructor injection - ResponseFactory autowired by container
    public function __construct(
        private readonly ResponseFactory $responseFactory = new ResponseFactory(),
    ) {
    }

    // Home page showing PSR-7 request information
    public function home(ServerRequestInterface $request): ResponseInterface
    {
        $currentTime = ClockFactory::timezone(Timezone::EUROPE_WARSAW)
            ->format(TimeFormat::DATETIME);

        // Build HTML response showing PSR-7 data
        $html = <<<HTML
            <!DOCTYPE html>
            <html>
            <head><title>Larafony Demo</title></head>
            <body>
                <h1>Larafony Framework Demo</h1>
                <div class="info">
                    <h2>PSR-7/17 Implementation Active</h2>
                    <p><strong>Request Method:</strong> {$request->getMethod()}</p>
                    <p><strong>Request URI:</strong> {$request->getUri()}</p>
                    <p><strong>Protocol:</strong> HTTP/{$request->getProtocolVersion()}</p>
                    <p><strong>Current Time:</strong> {$currentTime}</p>
                </div>
                <ul>
                    <li><a href="/info">📊 View Request Info (JSON)</a></li>
                    <li><a href="/error">⚠️ Trigger E_WARNING</a></li>
                </ul>
            </body>
            </html>
            HTML;

        // Create PSR-7 response with fluent interface
        // Each with*() method returns new Response instance (immutability)
        return $this->responseFactory->createResponse(200)
            ->withContent($html)
            ->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    // JSON endpoint demonstrating PSR-7 request data access
    public function info(ServerRequestInterface $request): ResponseInterface
    {
        // Extract all PSR-7 request data
        $data = [
            'method' => $request->getMethod(),              // GET, POST, etc.
            'uri' => (string) $request->getUri(),           // Full URI
            'protocol' => 'HTTP/' . $request->getProtocolVersion(),  // HTTP/1.1
            'headers' => $request->getHeaders(),            // All headers as array
            'query_params' => $request->getQueryParams(),   // $_GET
            'parsed_body' => $request->getParsedBody(),     // $_POST or JSON
            'server_params' => array_filter(
                $request->getServerParams(),                 // $_SERVER
                static fn ($key) => ! str_starts_with($key, 'HTTP_'),
                ARRAY_FILTER_USE_KEY,
            ),
        ];

        // Return JSON response with automatic Content-Type header
        return $this->responseFactory->createResponse(200)->withJson($data);
    }

    // 404 handler showing request-aware error page
    public function handleNotFound(ServerRequestInterface $request): ResponseInterface
    {
        // Access requested path from PSR-7 URI
        $path = $request->getUri()->getPath();

        $html = <<<HTML
            <!DOCTYPE html>
            <html>
            <head><title>404 Not Found</title></head>
            <body>
                <h1>404 - Page Not Found</h1>
                <p>The page <code>{$path}</code> does not exist.</p>
                <p><a href="/">← Go back home</a></p>
            </body>
            </html>
            HTML;

        // Return 404 response with custom body
        return $this->responseFactory->createResponse(404)
            ->withContent($html)
            ->withHeader('Content-Type', 'text/html; charset=utf-8');
    }
}
```

**What's happening here:**

1. **Constructor Injection** (lines 18-20): ResponseFactory injected via container autowiring
   - No need to manually instantiate factory
   - Default value ensures class works standalone for testing

2. **PSR-7 Type Hints** (line 24): All methods accept ServerRequestInterface and return ResponseInterface
   - Type-safe contract throughout application
   - Easy to test with mock requests/responses
   - Compatible with any PSR-7 implementation

3. **Request Data Access** (lines 26-42): Use PSR-7 methods instead of superglobals
   - `$request->getMethod()` instead of `$_SERVER['REQUEST_METHOD']`
   - `$request->getUri()` instead of parsing `$_SERVER['REQUEST_URI']`
   - `$request->getQueryParams()` instead of `$_GET`

4. **Immutable Response Building** (lines 52-54): Fluent interface with method chaining
   - `createResponse(200)` creates base response
   - `->withContent()` returns NEW response with body
   - `->withHeader()` returns NEW response with header
   - Each step creates new instance (immutability)

5. **JSON Response** (line 77): withJson() helper automatically encodes and sets Content-Type
   - Eliminates manual json_encode() calls
   - Automatically adds `Content-Type: application/json` header

6. **Request-Aware Errors** (line 82): 404 page shows the requested path from PSR-7 URI
   - Demonstrates accessing request data in error handlers
   - Path comes from immutable URI object, not $_SERVER

### Running the Demo

```bash
cd framework/demo-app
php8.5 -S localhost:8000 -t public
```

Then visit:
- `http://localhost:8000/` - Homepage with PSR-7 request info
- `http://localhost:8000/info` - JSON dump of all request data
- `http://localhost:8000/error` - Trigger error handler
- `http://localhost:8000/nonexistent` - 404 page showing requested path

**Expected output for `/info`:**

```json
{
  "method": "GET",
  "uri": "http://localhost:8000/info",
  "protocol": "HTTP/1.1",
  "headers": {
    "Host": ["localhost:8000"],
    "User-Agent": ["Mozilla/5.0..."],
    "Accept": ["text/html,application/json..."]
  },
  "query_params": {},
  "parsed_body": null,
  "server_params": {
    "REQUEST_METHOD": "GET",
    "REQUEST_URI": "/info",
    "SERVER_PROTOCOL": "HTTP/1.1"
  }
}
```

### Key Takeaways

- **PSR-7 Throughout:** Entire request/response cycle uses immutable PSR-7 objects, no direct superglobal access

- **Dependency Injection:** ResponseFactory injected into controller via container autowiring

- **Type Safety:** All methods type-hint PSR-7 interfaces, ensuring contract compliance

- **Immutability Benefits:** Can pass requests/responses through middleware without side effects

- **Factory Pattern:** ServerRequestFactory::fromGlobals() centralizes superglobal parsing

- **Application Class:** Provides emit() for PSR-7 response output and service provider integration

- **Controller Pattern:** Clean separation - controllers accept ServerRequestInterface, return ResponseInterface

- **Testability:** Easy to test controllers with mock PSR-7 requests, no superglobal mocking needed

- **Framework Evolution:**
  - **Chapter 4:** Container with service providers
  - **Chapter 5:** PSR-7 HTTP layer with Application class
  - **Future:** Routing (Chapter 6), Middleware (PSR-15), ORM, etc. all built on PSR-7 foundation

---

📚 **Learn More:** This implementation is explained in detail with step-by-step tutorials, tests, and best practices at [masterphp.eu](https://masterphp.eu)
