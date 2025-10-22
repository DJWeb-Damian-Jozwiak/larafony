# Chapter 8: Configuration and Environment Management

> Part of the Larafony Framework - A modern PHP 8.5 framework built from scratch
>
> 📚 Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)

## Overview

Chapter 8 introduces a comprehensive configuration and environment management system to the Larafony framework. This implementation provides a robust, type-safe approach to loading environment variables from `.env` files and managing application configuration through PHP-based config files.

The system is built from scratch without external dependencies, featuring a custom dotenv parser that handles quoted values, escape sequences, comments, and multiline scenarios. It follows the principle of separation of concerns with dedicated parser components for different aspects of .env file processing. The configuration system integrates seamlessly with the existing dependency injection container and service provider architecture.

This chapter demonstrates how to build production-ready configuration management that respects existing environment variables (never overwrites), provides both parsing and loading capabilities, and offers a clean API through static facades and service providers.

## Key Components

### Environment Loading

- **EnvironmentLoader** - Main facade for loading `.env` files and setting environment variables in `$_ENV`, `$_SERVER`, and via `putenv()`
- **EnvReader** - Static helper for reading environment variables with default value support

### Parsing System

- **DotenvParser** - Main parser implementing `ParserContract`, orchestrates line parsing using pipe operator for functional composition
- **LineParser** - Parses individual lines, identifies line types (empty, comment, variable), maintains line numbering
- **ValueParser** - Handles value parsing with support for single/double quotes and escape sequences (`\n`, `\r`, `\t`, `\"`, `\\`)

### Configuration Management

- **ConfigBase** - Core configuration container extending `DotContainer` (dot notation support), loads both `.env` files and PHP config files from `config/` directory
- **ConfigContract** - Interface defining configuration behavior
- **Config** - Static facade providing global access to configuration via `Config::get()` and `Config::set()`

### Data Transfer Objects

- **ParserResult** - Contains parsed variables, all lines, and statistics using PHP 8.5's `private(set)` for immutability
- **EnvironmentVariable** - Readonly DTO representing a single environment variable with metadata (quoted, multiline, line number)
- **ParsedLine** - Represents a parsed line with type and optional variable
- **LineType** - Enum for line types (Empty, Comment, Variable)

### Service Provider

- **ConfigServiceProvider** - Registers configuration services in DI container and boots config loading automatically

### Exception Hierarchy

- **EnvironmentError** - Base exception for file access errors
- **ParseError** - Thrown for invalid syntax in `.env` files
- **ValidationError** - For validation failures

## PSR Standards Implemented

While this chapter doesn't directly implement a specific PSR standard, it follows PSR principles and integrates with existing PSR implementations:

- **PSR-11**: Container integration - Configuration classes work seamlessly with the PSR-11 compliant DI container
- **PSR-4**: Autoloading - All classes follow PSR-4 namespace and directory structure

## Usage Examples

### Basic Example - Loading Environment Variables

```php
<?php

use Larafony\Framework\Config\Environment\EnvironmentLoader;

// Load .env file and set environment variables
$loader = new EnvironmentLoader();
$result = $loader->load(__DIR__ . '/.env');

// Access variables
echo $_ENV['APP_NAME']; // Larafony
echo getenv('APP_URL'); // https://larafony.local

// Check what was loaded
echo $result->count(); // Number of variables loaded
```

### Accessing Configuration via Facade

```php
<?php

use Larafony\Framework\Web\Config;
use Larafony\Framework\Config\Environment\EnvReader;

// In config files - use EnvReader to get env variables
// config/app.php
return [
    'name' => EnvReader::read('APP_NAME'),
    'url' => EnvReader::read('APP_URL'),
    'debug' => EnvReader::read('APP_DEBUG', false), // with default
];

// Anywhere in your app - use Config facade
$appName = Config::get('app.name'); // Uses dot notation
$debugMode = Config::get('app.debug', false); // With default value

// Set configuration at runtime
Config::set('app.custom_setting', 'value');
```

### Advanced Example - Custom Parser Integration

```php
<?php

use Larafony\Framework\Config\Environment\EnvironmentLoader;
use Larafony\Framework\Config\Environment\Parser\DotenvParser;

// Parse content without loading to environment (testing/validation)
$loader = new EnvironmentLoader();
$content = <<<ENV
APP_NAME="Larafony Framework"
APP_ENV=production
# This is a comment
DATABASE_URL="mysql://user:pass@localhost/db"
ENV;

$result = $loader->parseContent($content);

// Inspect parsed data
foreach ($result->variables as $var) {
    echo "{$var->key} = {$var->value} (quoted: {$var->isQuoted})\n";
}

// Check parsing statistics
echo "Total lines: {$result->totalLines}\n";
echo "Variables: {$result->count()}\n";
```

### Service Provider Integration

```php
<?php

use Larafony\Framework\Config\ServiceProviders\ConfigServiceProvider;
use Larafony\Framework\Web\Application;

// In your application bootstrap
$app = Application::instance(base_path: __DIR__);

// Register config service provider
$app->withServiceProviders([
    ConfigServiceProvider::class, // Auto-loads .env and config files
]);

// Config is now available throughout the app
$app->get(ConfigContract::class)->get('app.name');
```

## Comparison with Other Frameworks

| Feature | Larafony | Laravel | Symfony |
|---------|----------|---------|---------|
| Dotenv Library | Custom built-in parser | vlucas/phpdotenv package | symfony/dotenv component |
| Config File Format | PHP arrays | PHP arrays | YAML/XML/PHP |
| Environment Loading | `EnvironmentLoader` class | `Dotenv` facade | `Dotenv` component |
| Variable Access | `EnvReader::read()` + `Config::get()` | `env()` + `config()` helpers | `$_ENV` + `ParameterBag` |
| Overwrite Protection | Yes (respects existing vars) | Configurable via `overload()` | Configurable via `overload()` |
| Config Caching | Not yet implemented | Yes (`config:cache`) | Yes (cached container) |
| Dot Notation | Yes (via `DotContainer`) | Yes | No (array access) |
| Escape Sequences | Yes (`\n`, `\r`, `\t`, `\"`, `\\`) | Yes (via phpdotenv) | Yes (via symfony/dotenv) |
| Service Provider | `ConfigServiceProvider` | `ConfigServiceProvider` | Built into Kernel |
| Static Facade | `Config::get()` / `Config::set()` | `config()` helper | No static facade |
| Dependencies | Zero (built from scratch) | External package | External component |

**Key Differences:**

- **From-Scratch Implementation**: Larafony builds its own dotenv parser without external dependencies, providing full control and learning value. Laravel uses the popular `vlucas/phpdotenv` package, while Symfony uses its own `symfony/dotenv` component.

- **Parser Architecture**: Larafony uses a clean separation with dedicated parsers (`DotenvParser`, `LineParser`, `ValueParser`) and leverages PHP 8.5's pipe operator for functional composition. This makes the parsing logic more testable and maintainable.

- **PHP 8.5 Features**: Larafony uses modern PHP 8.5 features like `private(set)` for asymmetric visibility in `ParserResult`, readonly properties in DTOs, and the pipe operator for elegant data transformations.

- **Integration Pattern**: All three frameworks integrate configuration into their DI containers, but Larafony emphasizes the service provider pattern for bootstrapping and provides both direct class access and static facade patterns.

- **Immutability by Design**: Larafony's use of readonly DTOs and `private(set)` properties ensures parsed data cannot be accidentally modified, providing stronger guarantees than traditional approaches.

## Real World Integration

This chapter's features are demonstrated in the demo application with real-world usage examples showing how environment variables and configuration files work together in a practical application.

### Demo Application Changes

The demo application now includes:
- `.env` and `.env.example` files for environment configuration
- `config/app.php` configuration file that reads from environment variables
- Updated `bootstrap/web_app.php` to register the `ConfigServiceProvider`

### File Structure
```
demo-app/
├── .env                      # Actual environment variables (not committed)
├── .env.example              # Template for environment variables
├── config/
│   └── app.php              # Application configuration using EnvReader
└── bootstrap/
    └── web_app.php          # Bootstrap file registering ConfigServiceProvider
```

### Implementation Example

**File: `demo-app/.env.example`**

```bash
# Application Configuration
APP_NAME=Larafony
APP_URL=https://larafony.local
```

This file serves as a template showing developers which environment variables the application expects. The actual `.env` file (not committed to version control) contains the real values.

**File: `demo-app/.env`**

```bash
APP_NAME=Larafony
APP_URL=https://larafony.local
```

The actual environment file used by the application. In production, this file would contain production-specific values.

**File: `demo-app/config/app.php`**

```php
<?php

declare(strict_types=1);

use Larafony\Framework\Config\Environment\EnvReader;

// This config file demonstrates how to use EnvReader to access environment variables
// The EnvReader::read() method retrieves values from $_ENV that were loaded
// by the EnvironmentLoader during application bootstrap

return [
    // Read APP_NAME from environment variables
    // This will be available as Config::get('app.name')
    'name' => EnvReader::read('APP_NAME'),

    // Read APP_URL from environment variables
    // This will be available as Config::get('app.url')
    'url' => EnvReader::read('APP_URL'),
];
```

**What's happening here:**
1. `EnvReader::read()` is a static helper that reads from the `$_ENV` superglobal
2. The `.env` file was already loaded by `EnvironmentLoader` before this config file runs
3. This config file returns an array that will be stored under the 'app' key in the config container
4. The filename (`app.php`) becomes the config key, so these values are accessed via `Config::get('app.name')`

**File: `demo-app/bootstrap/web_app.php`**

```php
<?php

declare(strict_types=1);

use App\Http\Controllers\DemoController;
use Larafony\Framework\Config\ServiceProviders\ConfigServiceProvider;
use Larafony\Framework\ErrorHandler\ServiceProviders\ErrorHandlerServiceProvider;
use Larafony\Framework\Http\ServiceProviders\HttpServiceProvider;
use Larafony\Framework\Routing\Basic\Router;
use Larafony\Framework\Routing\ServiceProviders\RouteServiceProvider;

require_once __DIR__ . '/../vendor/autoload.php';

// Create application instance with base path
$app = \Larafony\Framework\Web\Application::instance(base_path: dirname(__DIR__));

// Register service providers - note ConfigServiceProvider is now included
$app->withServiceProviders([
    ErrorHandlerServiceProvider::class,
    HttpServiceProvider::class,
    RouteServiceProvider::class,
    ConfigServiceProvider::class,  // NEW: Registers configuration system
]);

// Define application routes
$app->withRoutes(static function (Router $router): void {
    $router->addRouteByParams('GET', '/', [DemoController::class, 'home']);
    $router->addRouteByParams('GET', '/info', [DemoController::class, 'info']);
    $router->addRouteByParams('GET', '/error', [DemoController::class, 'handleError']);
    $router->addRouteByParams('GET', '/exception', [DemoController::class, 'handleException']);
    $router->addRouteByParams('GET', '/fatal', [DemoController::class, 'handleFatal']);
});

return $app;
```

**What's happening here:**
1. **Application Initialization**: `Application::instance()` is called with `base_path` parameter pointing to the demo-app directory
2. **Service Provider Registration**: `ConfigServiceProvider` is added to the list of service providers
3. **Automatic Bootstrap**: When `ConfigServiceProvider::boot()` runs, it:
   - Registers `EnvironmentLoader`, `ConfigBase`, and `ConfigContract` in the DI container
   - Calls `ConfigContract::loadConfig()` which triggers:
     - Loading of `.env` file via `EnvironmentLoader` (sets `$_ENV`, `$_SERVER`, `putenv()`)
     - Scanning of `config/` directory for PHP files
     - Loading each config file and storing under its filename as key
4. **Configuration Available**: After bootstrap, config is accessible anywhere via `Config::get('app.name')`

### Running the Demo

```bash
cd framework/demo-app

# View the environment file
cat .env

# Check if config is loaded correctly - would need to add debug route or console command
# For now, the config is loaded automatically when the app boots
```

**Expected behavior:**
- When the application starts, the `.env` file is automatically loaded
- Environment variables are set in `$_ENV`, `$_SERVER`, and via `putenv()`
- Config files in `config/` directory are loaded and accessible via `Config::get()`
- Controllers can access config values: `Config::get('app.name')` returns "Larafony"

### Key Takeaways

- **Separation of Concerns**: Environment variables (`.env`) are separate from structured configuration (`config/*.php`)
- **Environment First**: `.env` is loaded first, then config files can use `EnvReader::read()` to reference those variables
- **Service Provider Pattern**: The entire configuration system bootstraps automatically through `ConfigServiceProvider`
- **Dot Notation Access**: Config uses dot notation (`app.name`) for intuitive hierarchical access
- **Type Safety**: Config values maintain their PHP types (strings, arrays, objects) unlike pure environment variables
- **Development Workflow**: `.env.example` serves as documentation, actual `.env` holds secrets and local settings
- **No Overwrites**: Existing environment variables are never overwritten, respecting system-level or container-level configurations

---

📚 **Learn More:** This implementation is explained in detail with step-by-step tutorials, tests, and best practices at [masterphp.eu](https://masterphp.eu)
