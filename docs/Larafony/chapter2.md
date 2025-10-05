# Chapter 2: Error Handling

This chapter covers the implementation of a robust error and exception handling system in Larafony Framework.

## Overview

Larafony's error handler provides:
- **Unified error and exception handling** - All PHP errors and uncaught exceptions are handled consistently
- **Beautiful error pages** - Detailed error information in development, clean messages in production
- **Stack trace analysis** - Full stack traces with code snippets for debugging

## Architecture

The error handling system consists of two main components:

### 1. ErrorHandler (`src/Larafony/ErrorHandler/DetailedErrorHandler.php`)

The main entry point that registers PHP error and exception handlers:

```php
new DetailedErrorHandler()->register();
```

This sets up:
- `set_error_handler()` - Converts PHP errors to ErrorException
- `set_exception_handler()` - Catches uncaught exceptions
- `register_shutdown_function()` - Catches fatal errors

### 2. ErrorRenderer (`src/Larafony/Formatters/HtmlErrorFormatter.php`)

Responsible for rendering error pages with:
- Exception type and message
- File location and line number
- Full stack trace with code context

## Usage

### Basic Setup

In your application's entry point (e.g., `public/index.php`):

```php
<?php

declare(strict_types=1);

use Larafony\Framework\ErrorHandler\DetailedErrorHandler;
use Uri\Rfc3986\Uri;

require_once __DIR__ . '/../vendor/autoload.php';

new DetailedErrorHandler()->register();

// Your application code here
```

## Demo Application

The project includes a demo application showcasing the error handler in action.

### Project Structure with Symlinks

The demo application uses two levels of symlinks for convenient development:

```
book/
├── framework/               # Main framework package
│   ├── src/
│   ├── tests/
│   └── demo-app/           # Demo application
│       ├── composer.json
│       ├── public/
│       │   └── index.php
│       └── vendor/
│           └── larafony/
│               └── framework/ 
└── demo-app/               # Symlink to framework/demo-app
```

This structure allows:
1. **Framework development** in `book/framework/` with immediate testing in demo-app
2. **Convenient access** via `book/demo-app/` symlink
3. **Real-time updates** without reinstalling packages

### Setup

The demo application's `composer.json` uses Composer's path repository with symlink:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../",
            "options": {
                "symlink": true
            }
        }
    ],
    "require": {
        "larafony/framework": "@dev"
    }
}
```

This creates `demo-app/vendor/larafony/framework` as a symlink to the framework root.

### Installation

From the project root:

```bash
cd framework/demo-app
# or
cd demo-app  # Using the symlink

php8.5 /usr/local/bin/composer install
```

### Running the Demo

Start PHP's built-in server:

```bash
cd demo-app/public
php8.5 -S localhost:8000
```

Visit http://localhost:8000 to see:
- **/** - Home page with navigation
- **/error** - Triggers E_USER_WARNING
- **/exception** - Throws RuntimeException
- **/fatal** - Triggers fatal error

### Demo Code Structure

```
demo-app/                   # Symlink to framework/demo-app
├── composer.json          # Dependencies with symlink to framework
├── public/
│   └── index.php         # Entry point with ErrorHandler registered
└── vendor/
    └── larafony/
        └── framework/    # Symlink to framework root
```
## Testing

The error handler includes comprehensive tests:

```bash
cd framework
composer test
```

Tests cover:
- Error handler registration
- Error to exception conversion
- Stack trace formatting
- Code snippet extraction
- HTML rendering

## Development Workflow

### 1. Symlink Verification

Check the symlinks are working:

```bash
# Main demo-app symlink
ls -la book/
# Should show: demo-app -> framework/demo-app

# Composer framework symlink
ls -la demo-app/vendor/larafony/
# Should show: framework -> ../../..//
```

## Related Documentation

- [Framework README](../../README.md)
- [Chapter 1: Project Setup](./chapter1.md)
- [Nginx Configuration](../nginx.md)
- [Apache Configuration](../apache.md)

## References

- [PHP Error Handling](https://www.php.net/manual/en/book.errorfunc.php)
- [PHP Exceptions](https://www.php.net/manual/en/language.exceptions.php)
- [Composer Path Repositories](https://getcomposer.org/doc/05-repositories.md#path)
