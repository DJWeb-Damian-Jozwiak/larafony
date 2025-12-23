# Chapter 32: Bridge Packages - Integrating External Libraries

> Part of the Larafony Framework - A modern PHP 8.5 framework built from scratch
>
> Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)

## Overview

This chapter covers bridge packages - official integrations that replace or extend Larafony's native implementations with popular PHP libraries. Bridges follow a simple pattern: swap the service provider and your application code remains unchanged because bridges implement the same interfaces.

The bridges covered in this chapter:

1. **larafony/env-phpdotenv** - Replace native EnvironmentLoader with vlucas/phpdotenv
2. **larafony/storage-flysystem** - Add multi-backend storage with League Flysystem

## Key Concepts

### Why Bridges?

Larafony's core framework intentionally has minimal dependencies - only PSR packages in production. However, many applications need advanced features provided by mature libraries:

- **phpdotenv** - Nested variables, multiline values, variable expansion
- **Flysystem** - S3, FTP, SFTP, and more storage backends

Bridges provide these features while maintaining Larafony's clean architecture:

1. **Drop-in Replacement** - Swap service provider, no code changes
2. **Interface Compliance** - Bridges implement the same contracts as native implementations
3. **Minimal Footprint** - Each bridge is a separate composer package

### Bridge Pattern

All Larafony bridges follow this pattern:

```
┌─────────────────────────────────────────────────────────────┐
│                     Your Application                         │
│            (uses interfaces, not implementations)            │
├─────────────────────────────────────────────────────────────┤
│                    Service Provider                          │
│         (swappable: native OR bridge provider)              │
├──────────────────────┬──────────────────────────────────────┤
│   Native Impl        │        Bridge Impl                   │
│   (zero deps)        │   (wraps external library)           │
└──────────────────────┴──────────────────────────────────────┘
```

## Bridge: larafony/env-phpdotenv

### Installation

```bash
composer require larafony/env-phpdotenv
```

### What It Replaces

Replaces Larafony's native `EnvironmentLoader` with `vlucas/phpdotenv` for enhanced .env file parsing.

### PhpdotenvLoader

**Location:** `src/PhpdotenvLoader.php`

```php
<?php

declare(strict_types=1);

namespace Larafony\Env\Phpdotenv;

use Dotenv\Dotenv;

class PhpdotenvLoader
{
    private ?Dotenv $dotenv = null;

    public function __construct(
        private readonly string $path,
        private readonly string $filename = '.env',
    ) {
    }

    public function load(): void
    {
        $this->dotenv = Dotenv::createImmutable($this->path, $this->filename);
        $this->dotenv->safeLoad();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;
    }

    public function require(string $key): void
    {
        $this->dotenv?->required($key);
    }

    public function requireWithValues(string $key, array $allowedValues): void
    {
        $this->dotenv?->required($key)->allowedValues($allowedValues);
    }
}
```

**Features over native:**
- Nested variable expansion: `${BASE_URL}/api`
- Multiline values support
- Variable validation with `required()` and `allowedValues()`
- Safe loading (doesn't overwrite existing env vars)

### Service Provider

**Location:** `src/ServiceProviders/PhpdotenvServiceProvider.php`

```php
<?php

declare(strict_types=1);

namespace Larafony\Env\Phpdotenv\ServiceProviders;

use Larafony\Env\Phpdotenv\PhpdotenvLoader;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Container\ServiceProvider;

class PhpdotenvServiceProvider extends ServiceProvider
{
    public function providers(): array
    {
        return [
            PhpdotenvLoader::class => PhpdotenvLoader::class,
        ];
    }

    public function register(ContainerContract $container): self
    {
        $basePath = $container->has('base_path')
            ? $container->get('base_path')
            : getcwd();

        $loader = new PhpdotenvLoader($basePath);
        $loader->load();

        $container->set(PhpdotenvLoader::class, $loader);

        return $this;
    }
}
```

### Usage

```php
// Register in config/providers.php
return [
    // Remove or comment out native loader if any
    Larafony\Env\Phpdotenv\ServiceProviders\PhpdotenvServiceProvider::class,
];

// .env file with advanced features
// APP_URL=https://example.com
// API_URL=${APP_URL}/api
// PRIVATE_KEY="-----BEGIN RSA PRIVATE KEY-----
// MIIEpQIBAAKCAQEA...
// -----END RSA PRIVATE KEY-----"

// Usage - environment variables are automatically available
$apiUrl = $_ENV['API_URL']; // https://example.com/api (expanded!)
```

## Bridge: larafony/storage-flysystem

### Installation

```bash
composer require larafony/storage-flysystem

# Optional: install adapters for other backends
composer require league/flysystem-aws-s3-v3    # Amazon S3
composer require league/flysystem-ftp          # FTP
composer require league/flysystem-sftp-v3      # SFTP
```

### What It Provides

Adds League Flysystem integration for unified filesystem operations across multiple backends:

- Local filesystem
- Amazon S3
- FTP
- SFTP
- And many more via Flysystem adapters

### FlysystemStorage

**Location:** `src/FlysystemStorage.php`

```php
<?php

declare(strict_types=1);

namespace Larafony\Storage\Flysystem;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;

class FlysystemStorage
{
    private Filesystem $filesystem;

    public function __construct(FilesystemAdapter $adapter)
    {
        $this->filesystem = new Filesystem($adapter);
    }

    public static function local(string $rootPath): self
    {
        return new self(new LocalFilesystemAdapter($rootPath));
    }

    public static function withAdapter(FilesystemAdapter $adapter): self
    {
        return new self($adapter);
    }

    // File operations
    public function put(string $path, string $contents): void;
    public function putStream(string $path, $stream): void;
    public function get(string $path): string;
    public function getStream(string $path);
    public function exists(string $path): bool;
    public function delete(string $path): void;
    public function copy(string $source, string $destination): void;
    public function move(string $source, string $destination): void;

    // Directory operations
    public function directoryExists(string $path): bool;
    public function createDirectory(string $path): void;
    public function deleteDirectory(string $path): void;
    public function listContents(string $path = '', bool $deep = false): array;

    // Metadata
    public function size(string $path): int;
    public function lastModified(string $path): int;
    public function mimeType(string $path): string;
    public function setVisibility(string $path, string $visibility): void;
    public function getVisibility(string $path): string;

    // Convenience methods
    public function prepend(string $path, string $contents): void;
    public function append(string $path, string $contents): void;
}
```

### FlysystemFactory

**Location:** `src/FlysystemFactory.php`

Multi-disk support for different storage backends:

```php
<?php

declare(strict_types=1);

namespace Larafony\Storage\Flysystem;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;

class FlysystemFactory
{
    private array $disks = [];

    public function __construct(
        private readonly array $config = [],
    ) {
    }

    public function disk(?string $name = null): FlysystemStorage
    {
        $name ??= $this->getDefaultDisk();

        if (!isset($this->disks[$name])) {
            $this->disks[$name] = $this->createDisk($name);
        }

        return $this->disks[$name];
    }

    public function getDefaultDisk(): string
    {
        return $this->config['default'] ?? 'local';
    }

    private function createDisk(string $name): FlysystemStorage
    {
        $diskConfig = $this->config['disks'][$name] ?? null;

        if ($diskConfig === null) {
            throw new \InvalidArgumentException("Disk [{$name}] is not configured.");
        }

        $adapter = $this->createAdapter($diskConfig);
        return FlysystemStorage::withAdapter($adapter);
    }

    private function createAdapter(array $config): FilesystemAdapter
    {
        $driver = $config['driver'] ?? 'local';

        return match ($driver) {
            'local' => $this->createLocalAdapter($config),
            's3' => $this->createS3Adapter($config),
            'ftp' => $this->createFtpAdapter($config),
            'sftp' => $this->createSftpAdapter($config),
            default => throw new \InvalidArgumentException("Unsupported driver [{$driver}]."),
        };
    }
}
```

### Service Provider

**Location:** `src/ServiceProviders/FlysystemServiceProvider.php`

```php
<?php

declare(strict_types=1);

namespace Larafony\Storage\Flysystem\ServiceProviders;

use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Container\ServiceProvider;
use Larafony\Storage\Flysystem\FlysystemFactory;
use Larafony\Storage\Flysystem\FlysystemStorage;

class FlysystemServiceProvider extends ServiceProvider
{
    public function providers(): array
    {
        return [
            FlysystemFactory::class => FlysystemFactory::class,
            FlysystemStorage::class => FlysystemStorage::class,
        ];
    }

    public function register(ContainerContract $container): self
    {
        $config = $container->get(ConfigContract::class);
        $filesystemConfig = $config->get('filesystems', []);

        $factory = new FlysystemFactory($filesystemConfig);

        $container->set(FlysystemFactory::class, $factory);
        $container->set(FlysystemStorage::class, $factory->disk());

        return $this;
    }
}
```

### Configuration

**config/filesystems.php:**

```php
<?php

return [
    'default' => 'local',

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'prefix' => env('AWS_PREFIX', ''),
        ],

        'ftp' => [
            'driver' => 'ftp',
            'host' => env('FTP_HOST'),
            'username' => env('FTP_USERNAME'),
            'password' => env('FTP_PASSWORD'),
            'port' => (int) env('FTP_PORT', 21),
            'root' => env('FTP_ROOT', '/'),
            'passive' => true,
            'ssl' => false,
        ],

        'sftp' => [
            'driver' => 'sftp',
            'host' => env('SFTP_HOST'),
            'username' => env('SFTP_USERNAME'),
            'password' => env('SFTP_PASSWORD'),
            'privateKey' => env('SFTP_PRIVATE_KEY'),
            'port' => (int) env('SFTP_PORT', 22),
            'root' => env('SFTP_ROOT', '/'),
        ],
    ],
];
```

### Usage Examples

```php
// Get storage via container
$storage = $container->get(FlysystemStorage::class);

// Or use factory for specific disks
$factory = $container->get(FlysystemFactory::class);

// Basic file operations
$storage->put('uploads/photo.jpg', $imageContent);
$content = $storage->get('uploads/photo.jpg');

if ($storage->exists('uploads/photo.jpg')) {
    $storage->delete('uploads/photo.jpg');
}

// Directory operations
$storage->createDirectory('backups');
$files = $storage->listContents('uploads', deep: true);

foreach ($files as $file) {
    echo "{$file['path']} ({$file['type']})\n";
}

// Multi-disk usage
$localStorage = $factory->disk('local');
$s3Storage = $factory->disk('s3');

// Copy from local to S3
$content = $localStorage->get('backups/db.sql');
$s3Storage->put('backups/' . date('Y-m-d') . '/db.sql', $content);

// Stream large files
$stream = $localStorage->getStream('large-video.mp4');
$s3Storage->putStream('videos/large-video.mp4', $stream);
```

## Available Bridges

Summary of all official Larafony bridges:

| Package | Replaces | External Library |
|---------|----------|------------------|
| `larafony/clock-carbon` | Native Clock | nesbot/carbon |
| `larafony/log-monolog` | Native Logger | monolog/monolog |
| `larafony/http-guzzle` | Native cURL Client | guzzlehttp/guzzle |
| `larafony/mail-symfony` | Native SMTP | symfony/mailer |
| `larafony/view-twig` | Blade | twig/twig |
| `larafony/view-smarty` | Blade | smarty/smarty |
| `larafony/debugbar-php` | Native DebugBar | maximebf/debugbar |
| `larafony/websocket-react` | Native FiberEngine | react/socket |
| `larafony/env-phpdotenv` | Native EnvironmentLoader | vlucas/phpdotenv |
| `larafony/storage-flysystem` | Adds multi-backend storage | league/flysystem |

## Testing

Both bridges include comprehensive test suites:

```bash
# Phpdotenv bridge tests
cd bridges/larafony-env-phpdotenv
composer test  # 8 tests, 16 assertions

# Flysystem bridge tests
cd bridges/larafony-storage-flysystem
composer test  # 12 tests, 24 assertions
```

## Summary

This chapter delivered two essential bridge packages:

1. **larafony/env-phpdotenv** - Enhanced .env parsing with variable expansion, multiline support, and validation
2. **larafony/storage-flysystem** - Unified storage API across local, S3, FTP, and SFTP backends

Both bridges follow Larafony's principles:
- **Drop-in replacement** via service providers
- **Interface compliance** for seamless integration
- **Full test coverage** for reliability
- **PHPStan level 8** for type safety

The bridge pattern allows Larafony to maintain its minimal-dependency philosophy while providing easy access to battle-tested external libraries when needed.

---

Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)
