# Chapter 8: Environment Variables and Configuration

This chapter implements a zero-dependency .env parser and configuration system with full PHP 8.4/8.5 features including property hooks, pipe operator, and asymmetric visibility.

## Table of Contents

- [Overview](#overview)
- [Core Components](#core-components)
- [Environment Variables (.env)](#environment-variables-env)
- [Configuration Files](#configuration-files)
- [Static Config Facade](#static-config-facade)
- [Architecture Highlights](#architecture-highlights)
- [Integration Testing](#integration-testing)
- [Complete Examples](#complete-examples)

## Overview

Larafony's Configuration system provides:

- **Zero Dependencies** - Custom .env parser, no vlucas/phpdotenv needed
- **PHP 8.4/8.5 Features** - Property hooks, pipe operator, asymmetric visibility
- **SOLID Architecture** - Clean separation of concerns with contracts
- **Auto-loading** - ServiceProvider automatically loads .env and config files
- **Static Facade** - Simple `Config::get()` access without magic
- **Full Test Coverage** - 40 tests ensuring reliability
- **Integration Tested** - Real E2E test that catches breaking changes

## Core Components

### 1. EnvironmentLoader

Loads `.env` files and sets environment variables in `$_ENV`, `$_SERVER`, and `getenv()`.

```php
use Larafony\Framework\Config\Environment\EnvironmentLoader;

$loader = new EnvironmentLoader();

// Load .env file and set environment variables
$result = $loader->load('/path/to/.env');

// Or parse without setting variables
$result = $loader->parseContent("APP_NAME=Larafony\nAPP_ENV=local");

echo $_ENV['APP_NAME']; // Larafony
echo getenv('APP_ENV'); // local
```

**Features:**
- Parses .env files with full syntax support
- Sets variables in $_ENV, $_SERVER, and getenv()
- Respects existing variables (no overwrite by default)
- Throws `EnvironmentError` for missing/unreadable files

### 2. DotenvParser

The core parser that processes .env content using modern PHP features.

```php
use Larafony\Framework\Config\Environment\Parser\DotenvParser;

$parser = new DotenvParser();
$result = $parser->parse(file_get_contents('.env'));

// Access parsed variables
echo $result->get('APP_NAME');
$array = $result->toArray();

// Metadata
echo $result->count(); // Number of variables
echo $result->totalLines; // Total lines parsed
```

**Syntax Support:**
```bash
# Simple variables
APP_NAME=Larafony
APP_ENV=local

# Quoted values (single or double quotes)
APP_NAME="Larafony Framework"
APP_MOTTO='Build it yourself'

# Escape sequences in double quotes
MESSAGE="Line 1\nLine 2\tTabbed"

# Spaces around equals
KEY = value
KEY2= value2
KEY3 =value3

# Empty values
EMPTY_VAR=

# Comments
# This is a comment

# Blank lines are ignored
```

### 3. ConfigBase

The main configuration class that loads both .env files and config PHP files.

```php
use Larafony\Framework\Config\ConfigBase;
use Larafony\Framework\Web\Application;

$app = Application::instance();
$config = $app->get(ConfigBase::class);

// Automatically loads:
// 1. .env file from base path
// 2. All PHP files from config/ directory
$config->loadConfig();

// Access config values using dot notation
echo $config->get('app.name');
echo $config->get('app.url');
echo $config->get('database.host', 'localhost');

// Set values
$config->set('custom.key', 'value');

// Check existence
if ($config->has('app.debug')) {
    // ...
}
```

**How it works:**
1. Loads `.env` file and sets environment variables
2. Scans `config/` directory for PHP files
3. Each file becomes a config namespace (e.g., `config/app.php` → `app.name`)
4. Self-registers in container as `ConfigContract`

### 4. Config Static Facade

Simple static facade for convenient access without magic.

```php
use Larafony\Framework\Web\Config;

// Get values
$appName = Config::get('app.name');
$url = Config::get('app.url', 'http://localhost');

// Set values
Config::set('runtime.debug', true);
```

**Why not Laravel's magic approach?**
```php
// Laravel: Complex __callStatic magic in base Facade class
Config::get('app.name'); // Magically resolved

// Larafony: Simple 2-method class - that's all!
class Config {
    public static function get(string $key, mixed $default = null): mixed
    {
        return Application::instance()->get(ConfigContract::class)->get($key, $default);
    }

    public static function set(string $key, mixed $value): void
    {
        Application::instance()->get(ConfigContract::class)->set($key, $value);
    }
}
```

**Benefits:**
- No magic `__callStatic`
- Clear, debuggable code
- IDE autocomplete works perfectly
- Faster execution (no reflection)

## Environment Variables (.env)

### Creating .env File

```bash
# Application
APP_NAME="Larafony Framework"
APP_ENV=local
APP_DEBUG=true
APP_URL=https://larafony.local

```

### Using in Config Files

Config files can use `env()` helper to read environment variables:

```php
// config/app.php
return [
    'name' => env('APP_NAME', 'Larafony'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
];
```

### Environment Variable Priority

1. **Existing system variables** - Not overwritten by .env
2. **.env file** - Loaded if variable doesn't exist
3. **Default values** - Used in `env()` if not set

```php
// If $_ENV['APP_NAME'] already exists, .env won't overwrite it
$_ENV['APP_NAME'] = 'System Value';

$loader = new EnvironmentLoader();
$loader->load('.env'); // APP_NAME=File Value

echo $_ENV['APP_NAME']; // System Value (unchanged)
```

## Configuration Files

### File Structure

```
config/
├── app.php       → Config::get('app.name')
├── database.php  → Config::get('database.host')
├── cache.php     → Config::get('cache.driver')
└── session.php   → Config::get('session.lifetime')
```

### Example: config/app.php

```php
return [
    'name' => env('APP_NAME', 'Larafony'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => 'UTC',
    'locale' => 'en',
];
```

### Example: config/database.php

```php
return [
    'default' => env('DB_CONNECTION', 'mysql'),

    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', 3306),
            'database' => env('DB_DATABASE', 'larafony'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],
    ],
];
```

### Nested Access

```php
// Access nested values with dot notation
Config::get('database.connections.mysql.host'); // 127.0.0.1
Config::get('database.connections.mysql.port'); // 3306
Config::get('app.timezone'); // UTC
```

## Static Config Facade

### Basic Usage

```php
use Larafony\Framework\Web\Config;

// Get value
$name = Config::get('app.name');

// Get with default
$debug = Config::get('app.debug', false);

// Set value
Config::set('runtime.start_time', microtime(true));

// Use in any class without dependency injection
class SomeService
{
    public function process(): void
    {
        $appUrl = Config::get('app.url');
        // ...
    }
}
```

### Why Not Use Container Directly?

```php
// Without facade - verbose
$config = Application::instance()->get(ConfigContract::class);
$name = $config->get('app.name');

// With facade - clean
$name = Config::get('app.name');
```

## Architecture Highlights

### PHP 8.4 Property Hooks

**ParsedLine with property hooks:**
```php
final class ParsedLine
{
    // Property hooks replace getter methods
    public bool $isVariable {
        get => $this->type === LineType::Variable;
    }

    public bool $isComment {
        get => $this->type === LineType::Comment;
    }

    public bool $isEmpty {
        get => $this->type === LineType::Empty;
    }

    public function __construct(
        public readonly string $raw,
        public readonly LineType $type,
        public readonly ?EnvironmentVariable $variable = null,
        public readonly int $lineNumber = 0,
    ) {}
}

// Usage - looks like property access, executes hook
if ($parsedLine->isVariable) {
    echo $parsedLine->variable->key;
}
```

**ParserResult with asymmetric visibility:**
```php
final class ParserResult
{
    // Public read, private write - eliminates getter
    public private(set) array $variables;

    public function __construct(
        array $variables,
        public readonly array $lines = [],
        public readonly int $totalLines = 0,
    ) {
        $this->variables = [];
        foreach ($variables as $variable) {
            $this->variables[$variable->key] = $variable;
        }
    }
}

// Usage
echo count($result->variables); // ✓ Can read
$result->variables = []; // ✗ Cannot write (private set)
```

**ConfigServiceProvider with property hooks:**
```php
class ConfigServiceProvider extends ServiceProvider
{
    // Property hook replaces getProviders() method
    public array $providers {
        get => [
            EnvironmentLoader::class => EnvironmentLoader::class,
            ConfigBase::class => ConfigBase::class,
            ConfigContract::class => ConfigBase::class,
        ];
    }
}
```

### PHP 8.5 Pipe Operator

**DotenvParser using pipes:**
```php
public function parse(string $content): ParserResult
{
    $this->lineParser->reset();

    // Normalize line endings and split
    $lines = str_replace("\r\n", "\n", $content)
            |> (static fn (string $content) => explode("\n", $content));

    // Parse all lines
    $allParsedLines = array_map(fn (string $line) => $this->lineParser->parse($line), $lines)
        |> (static fn (array $lines) => array_filter($lines));

    // Extract variables using pipe chain
    $variables = array_filter($allParsedLines, static fn (ParsedLine $line) => $line->isVariable)
        |> (static fn (array $lines) => array_map(static fn (ParsedLine $line) => $line->variable, $lines))
        |> (static fn (array $variables) => array_filter($variables));

    return new ParserResult(
        variables: $variables,
        lines: $allParsedLines,
        totalLines: count($lines)
    );
}
```

**Benefits of pipe operator:**
- Data flows top-to-bottom (readable)
- No nested function calls
- Functional programming style
- Clear transformation pipeline

### SOLID Principles

**Single Responsibility:**
- `LineParser` - Parses single line
- `ValueParser` - Parses value with quotes/escapes
- `DotenvParser` - Orchestrates parsing
- `EnvironmentLoader` - Sets environment variables
- `ConfigBase` - Manages configuration

**Open/Closed:**
- `ParserContract` interface allows custom parsers
- `ConfigContract` interface allows custom implementations

**Dependency Inversion:**
- `DotenvParser` depends on `ParserContract`, not concrete parser
- `EnvironmentLoader` depends on `ParserContract`
- Services depend on `ConfigContract`, not `ConfigBase`

## Integration Testing

### The Safety Net

The integration test creates a **safety net** that catches breaking changes:

```php
use Larafony\Framework\Tests\Integration\ApplicationIntegrationTest;

class ApplicationIntegrationTest extends TestCase
{
    public function testApplicationRespondsWith200(): void
    {
        // 1. Load real app bootstrap
        $app = require __DIR__ . '/../../demo-app/bootstrap/web_app.php';
        $this->assertInstanceOf(Application::class, $app);

        // 2. Get URL from config (tests config loading)
        $factory = $app->get(RequestFactoryInterface::class);
        $url = Config::get('app.url');

        // 3. Make REAL HTTP request with PSR-18 client
        $response = HttpClientFactory::instance()->sendRequest(
            $factory->createRequest('GET', $url)
        );

        // 4. Verify 200 status
        $this->assertEquals(200, $response->getStatusCode());
    }
}
```

**What this catches:**
- ✓ Bootstrap loading errors
- ✓ ServiceProvider failures
- ✓ Config loading failures
- ✓ Routing errors
- ✓ Controller errors
- ✓ Response handling errors

**CI/CD Integration:**
- Add to pre-commit hook → Blocks broken commits
- Add to GitHub Actions → Blocks broken PRs
- Add to deployment pipeline → Blocks broken deployments

**Result:** If anything breaks in the entire stack, the test fails and you know immediately!

## Complete Examples


## Key Differences from vlucas/phpdotenv

| Feature | vlucas/phpdotenv | **Larafony Config** |
|---------|------------------|---------------------|
| External Dependency | ✓ (composer package) | **✗ (zero deps)** |
| PHP 8.4 Features | ✗ | **✓ (property hooks, asymmetric visibility)** |
| PHP 8.5 Features | ✗ | **✓ (pipe operator)** |
| SOLID Architecture | Basic | **✓ (full SOLID with contracts)** |
| DTO-based | ✗ | **✓ (immutable DTOs)** |
| Config File Support | ✗ (only .env) | **✓ (auto-loads config/)** |
| Static Facade | ✗ | **✓ (Config::get())** |
| Integration Tested | ✗ | **✓ (E2E HTTP test)** |
| Line Numbers in Errors | Basic | **✓ (full metadata)** |
| Property Hooks | ✗ | **✓ (isVariable, isComment, isEmpty)** |

## Testing

### Running Tests

```bash
# All config tests
php8.5 vendor/bin/phpunit tests/Larafony/Config --testdox

# Integration tests
php8.5 vendor/bin/phpunit tests/Integration --testdox

# Single test class
php8.5 vendor/bin/phpunit tests/Larafony/Config/Environment/Parser/DotenvParserTest.php
```

### Test Coverage

```
✓ DotenvParser (11 tests)
✓ ValueParser (10 tests)
✓ LineParser (10 tests)
✓ EnvironmentLoader (6 tests)
✓ Integration Tests (3 tests)

Total: 40 tests, 83 assertions
```

## Related Documentation

- [Framework README](../../README.md)
- [Chapter 4: Dependency Injection](./chapter4.md)
- [Chapter 7: PSR-18 HTTP Client](./chapter7.md)

## References

- [PHP 8.4 Property Hooks](https://wiki.php.net/rfc/property-hooks)
- [PHP 8.4 Asymmetric Visibility](https://wiki.php.net/rfc/asymmetric-visibility-v2)
- [PHP 8.5 Pipe Operator](https://wiki.php.net/rfc/pipe-operator-v2)
- [PSR-11: Container Interface](https://www.php-fig.org/psr/psr-11/)

## What's Next?

**Chapter 9** will introduce the Console Kernel, allowing you to create powerful command-line tools with argument parsing, output formatting
