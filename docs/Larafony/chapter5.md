# Chapter 5: PSR-7 HTTP Foundation & Property Hooks

This chapter implements PSR-7 HTTP message interfaces with modern PHP 8.4+ property hooks, providing a clean and type-safe foundation for HTTP handling.

## Overview

Larafony's HTTP layer provides:
- **PSR-7 compatible** - Full implementation of HTTP message interfaces
- **PSR-17 factories** - Request, Response, Stream, URI, and UploadedFile factories
- **Property hooks** - Modern PHP 8.5+ syntax for cleaner property management
- **Immutability** - All HTTP objects follow PSR-7 immutability principles
- **Zero dependencies** - Pure PHP implementation

## Architecture

### Core Components

#### 1. HTTP Messages (`src/Larafony/Http/`)

**Request** - Implements `Psr\Http\Message\RequestInterface`:
```php
final class Request implements RequestInterface
{
    public function getRequestTarget(): string;
    public function withMethod(string $method): RequestInterface;
    public function withUri(UriInterface $uri, bool $preserveHost = false): RequestInterface;
    // ... PSR-7 methods
}
```

**Response** - Implements `Psr\Http\Message\ResponseInterface`:
```php
final class Response implements ResponseInterface
{
    public function getStatusCode(): int;
    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface;
    // ... PSR-7 methods
}
```

**Stream** - Implements `Psr\Http\Message\StreamInterface` with property hooks:
```php
class Stream implements StreamInterface
{
    // Modern property hooks with InactivePropertyGuard
    private StreamSize $_size;
    private StreamSize $size {
        get => InactivePropertyGuard::get($this->_size, $this->detached, 'Operation not allowed in detached stream');
        set => $this->_size = $value;
    }
}
```

**Uri** - Implements `Psr\Http\Message\UriInterface`:
uses native PHP8.5 Uri class
```php
use Uri\Rfc3986\Uri;
readonly class UriManager implements UriInterface
{
    private Uri $uri;

    public function __construct(string $uri)
    {
        $this->uri = new Uri($uri);
    }

    // ... PSR-7 methods, simply native Uri wrapper
}
```

**UploadedFile** - Implements `Psr\Http\Message\UploadedFileInterface`:
```php
final class UploadedFile implements UploadedFileInterface
{
    public function moveTo(string $targetPath): void;
    public function getClientFilename(): ?string;
    public function getClientMediaType(): ?string;
    // ... PSR-7 methods
}
```

#### 2. PSR-17 Factories (`src/Larafony/Http/Factories/`)

All factories implement their respective PSR-17 interfaces:
- `RequestFactory` - Creates PSR-7 Request objects
- `ResponseFactory` - Creates PSR-7 Response objects
- `StreamFactory` - Creates PSR-7 Stream objects
- `UriFactory` - Creates PSR-7 Uri objects
- `UploadedFileFactory` - Creates PSR-7 UploadedFile objects
- `ServerRequestFactory` - Creates PSR-7 ServerRequest objects

#### 3. Property Hooks Support (`src/Larafony/Core/Support/`)

**InactivePropertyGuard** - Guard for property hooks preventing access to detached objects:
```php
final class InactivePropertyGuard
{
    /**
     * @template T
     * @param T $value
     * @throws RuntimeException if object is inactive
     */
    public static function get(mixed $value, bool $isInactive, string $message): mixed;
}
```

Used in Stream to prevent operations on detached streams:
```php
private StreamWrapper $wrapper {
    get => InactivePropertyGuard::get($this->_wrapper, $this->detached, self::MESSAGE);
    set => $this->_wrapper = $value;
}
```

## Key Features


### Immutability

All `with*()` methods use new PHP8.5 `clone with' syntax to create new objects.

## Usage Examples

### Creating Requests

```php
use Larafony\Framework\Http\Factories\RequestFactory;

$factory = new RequestFactory();
$request = $factory->createRequest('GET', 'https://example.com/api');
```

### Working with Streams

```php
use Larafony\Framework\Http\Factories\StreamFactory;

$factory = new StreamFactory();
$stream = $factory->createStream('Hello World');

echo $stream->getContents(); // "Hello World"
$stream->rewind();
echo $stream->read(5); // "Hello"
```

### Building URIs

```php
use Larafony\Framework\Http\Factories\UriFactory;

$factory = new UriFactory();
$uri = $factory->createUri('https://example.com/path?query=value#fragment');

echo $uri->getScheme(); // "https"
echo $uri->getPath();   // "/path"
```

## Files Structure

```
src/Larafony/
├── Http/
│   ├── Request.php
│   ├── Response.php
│   ├── Stream.php
│   ├── Uri.php
│   ├── UploadedFile.php
│   ├── ServerRequest.php
│   ├── Factories/
│   └── Helpers/
│       └── Stream/
└── Core/
    └── Support/
        └── InactivePropertyGuard.php

tests/Larafony/
├── Http/
│   ├── RequestTest.php
│   ├── ResponseTest.php
│   ├── StreamTest.php
│   ├── UriTest.php
│   └── Factories/
│       ├── RequestFactoryTest.php
│       ├── ResponseFactoryTest.php
│       └── ...
└── Core/
    └── Support/
        └── InactivePropertyGuardTest.php
```

## Key Differences from Other HTTP Implementations

| Feature                    | Laravel HTTP | Symfony HttpFoundation | **Larafony HTTP** |
|----------------------------|--------------|------------------------|-------------------|
| PSR-7 Compatible           | ✗ (mutable)  | ✗ (mutable)            | **✓**             |
| PSR-17 Factories           | ✗            | ✗                      | **✓**             |
| Property Hooks (PHP 8.4)   | ✗            | ✗                      | **✓**             |
| Native Uri class (PHP 8.5) | ✗            | ✗                      | **✓**             |
| Clone with syntax          | ✗            | ✗                      | **✓**             |
| Immutability               | Mutable      | Mutable                | **✓**             |
| Zero Dependencies          | ✗            | ✗                      | **✓ (PSR only)**  |
| Stream Guards              | ✗            | ✗                      | **✓ (InactivePropertyGuard)** |

#### Notes

- **PSR-7 Compatible** — Laravel’s and Symfony’s primary HTTP layers are mutable by design (DX-first). Larafony follows strict PSR-7 immutability: all mutations happen via `with*()` returning a new instance. *(Both ecosystems offer bridges/adapters for PSR-7 interop, but their core HTTP objects are not PSR-7.)*
- **PSR-17 Factories** — Larafony implements the standard factory interfaces for creating PSR-7 objects (requests, responses, streams, URIs, uploaded files). Laravel HTTP and Symfony HttpFoundation do not expose PSR-17 factories in their core; interop exists via separate adapters.
- **Property Hooks (PHP 8.4+)** — Used internally for clean, lazy, and guarded property access (e.g., parsed headers, body size) without leaking mutability into public APIs.
- **Native `Uri` class** — Larafony uses PHP’s RFC-3986 `Uri\Rfc3986\Uri` and exposes it through PSR-7’s `UriInterface` (no mutable wrappers). Interop remains straightforward.
- **Clone-with syntax (PHP 8.5+)** — Where available in your PHP runtime, Larafony supports the ergonomic `clone($obj, ['prop' => $value])` style; otherwise it provides equivalent PSR-7 `with*()` methods.
- **Immutability** — Larafony enforces immutability across request/response/URI/stream objects per PSR-7, favoring predictability and safe sharing. DX sugar is provided without sacrificing immutability.
- **Stream Guards** — `InactivePropertyGuard` and light checks prevent invalid operations on streams (e.g., read after `detach()`/`close()`, write to non-writable, rewind on non-seekable), improving runtime safety.
- **Zero Dependencies** — Only `psr/*` packages are required; no heavy runtime deps. Designed for easy interop with PSR-18 clients and PSR-15 middleware.

## Related Documentation

- [Framework README](../../README.md)
- [Chapter 1: Project Setup](./chapter1.md)
- [Chapter 2: Error Handling](./chapter2.md)
- [Chapter 3: Clock](./chapter3.md)
- [Chapter 4: Dependency Injection](./chapter4.md)

## References

- [PSR-7: HTTP Message Interface](https://www.php-fig.org/psr/psr-7/)
- [PSR-17: HTTP Factories](https://www.php-fig.org/psr/psr-17/)
- [PHP 8.5 Property Hooks RFC](https://wiki.php.net/rfc/property-hooks)
- [PHP 8.5 Native Uri class](https://wiki.php.net/rfc/uri)

## What's Next?

**Chapter 6** will introduce the routing system using PSR-15 middleware, building on top of this HTTP foundation.
