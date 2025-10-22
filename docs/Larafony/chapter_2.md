# Chapter 2: Error Handler

> Part of the Larafony Framework - A modern PHP 8.5 framework built from scratch
>
> 📚 Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)

## Overview

Chapter 2 introduces a robust error handling system to the Larafony framework. This component provides a developer-friendly way to handle exceptions, errors, and fatal errors with detailed, formatted output. The error handler converts PHP errors into exceptions, catches uncaught exceptions, and even handles fatal errors that occur during shutdown.

The implementation focuses on providing clear, readable error messages with full stack traces, making debugging easier during development. The system is built with a clean separation of concerns - the handler manages error registration and coordination, while formatters handle the presentation layer.

## Key Components

### Error Handling Core

- **ErrorHandler** (interface) - Contract defining error handler behavior with `handle()` and `register()` methods
- **DetailedErrorHandler** - Main implementation that registers error handlers, converts errors to exceptions, and manages fatal error handling

### Formatting

- **HtmlErrorFormatter** - Formats exceptions and fatal errors into styled HTML output with syntax highlighting, stack traces, and file locations

## PSR Standards Implemented

While this chapter doesn't directly implement a specific PSR standard, it follows PSR best practices:

- **PSR-4**: Autoloading for `Larafony\Framework\ErrorHandler\` namespace
- **Type Safety**: Strict typing with `declare(strict_types=1)` throughout
- **Interface Segregation**: Clean contract definition separating interface from implementation

## Usage Examples

### Basic Example

```php
<?php

use Larafony\Framework\ErrorHandler\DetailedErrorHandler;

require_once __DIR__ . '/vendor/autoload.php';

// Register the error handler
$handler = new DetailedErrorHandler();
$handler->register();

// Now all errors, warnings, and exceptions will be caught
throw new RuntimeException('Something went wrong!');
```

### Advanced Example with Custom Formatting

```php
<?php

use Larafony\Framework\ErrorHandler\DetailedErrorHandler;
use Larafony\Framework\ErrorHandler\Formatters\HtmlErrorFormatter;

require_once __DIR__ . '/vendor/autoload.php';

// Create handler with custom formatter
$formatter = new HtmlErrorFormatter();
$handler = new DetailedErrorHandler($formatter);
$handler->register();

// Trigger different types of errors
function demonstrateErrorHandling(): void
{
    // This warning will be converted to an exception
    trigger_error('This is a warning', E_USER_WARNING);

    // This exception will be caught and formatted
    throw new InvalidArgumentException('Invalid parameter provided');
}

demonstrateErrorHandling();
```

### Manual Error Handling

```php
<?php

use Larafony\Framework\ErrorHandler\DetailedErrorHandler;

$handler = new DetailedErrorHandler();

try {
    // Some risky operation
    riskyOperation();
} catch (Throwable $e) {
    // Manually handle the exception
    $handler->handle($e);
}
```

## Implementation Details

### DetailedErrorHandler

**Location:** `src/Larafony/ErrorHandler/DetailedErrorHandler.php:12`

**Purpose:** Main error handler that registers PHP error handlers and coordinates error/exception handling.

**Key Methods:**
- `register(): void` - Registers exception handler, error handler, and shutdown function for fatal errors
- `handle(Throwable $throwable): void` - Handles an exception by setting HTTP 500 status and outputting formatted error

**Dependencies:**
- `HtmlErrorFormatter` (optional, defaults to new instance)

**How it works:**
1. **Exception Handler**: Uses `set_exception_handler()` with first-class callable syntax (`$this->handle(...)`)
2. **Error Handler**: Converts all PHP errors to `ErrorException` using `set_error_handler()`
3. **Shutdown Handler**: Catches fatal errors via `register_shutdown_function()` and `error_get_last()`

**Fatal Error Detection:**
The handler identifies fatal errors by checking error types: `E_ERROR`, `E_PARSE`, `E_CORE_ERROR`, `E_COMPILE_ERROR`

### HtmlErrorFormatter

**Location:** `src/Larafony/ErrorHandler/Formatters/HtmlErrorFormatter.php:9`

**Purpose:** Formats exceptions and fatal errors into visually appealing HTML output.

**Key Methods:**
- `format(Throwable $throwable): string` - Formats a thrown exception
- `formatFatalError(array $error): string` - Formats a fatal error from `error_get_last()`

**Features:**
- Dark theme styling for better readability
- Color-coded sections (red for title, yellow for message, green for file location)
- Full stack trace output
- Emoji indicators (💥 for errors, 📁 for file path, 📚 for backtrace)
- XSS protection through `htmlspecialchars()`

**Usage:**
```php
$formatter = new HtmlErrorFormatter();
$exception = new RuntimeException('Test error');
echo $formatter->format($exception);
```

## Real World Integration

This chapter's features are demonstrated in the demo application with real-world usage examples.

### Demo Application Changes

The demo application provides an interactive way to test the error handler with different types of errors. It includes a simple router that demonstrates exception handling, error conversion, and fatal error catching.

### File Structure
```
demo-app/
├── public/
│   └── index.php          # Main entry point with error handler demo
└── composer.json          # Requires larafony/framework
```

### Implementation Example

**File: `demo-app/public/index.php`**

```php
<?php

declare(strict_types=1);

use Larafony\Framework\ErrorHandler\DetailedErrorHandler;
use Uri\Rfc3986\Uri;

require_once __DIR__ . '/../vendor/autoload.php';

// Register the error handler globally for the application
new DetailedErrorHandler()->register();

// Simple routing based on request path
$path = new Uri($_SERVER['REQUEST_URI'])->getPath();

match ($path) {
    '/' => handleHome(),
    '/error' => handleError(),           // Demonstrates error-to-exception conversion
    '/exception' => handleException(),   // Demonstrates exception handling
    '/fatal' => handleFatal(),          // Demonstrates fatal error handling
    default => handleNotFound(),
};

function handleHome(): void
{
    echo '<h1>Larafony Framework Demo</h1>';
    echo '<p>Error Handler is active. Try these endpoints:</p>';
    echo '<ul>';
    echo '<li><a href="/error">Trigger E_WARNING</a></li>';
    echo '<li><a href="/exception">Trigger Exception</a></li>';
    echo '<li><a href="/fatal">Trigger Fatal Error</a></li>';
    echo '</ul>';
}

function handleError(): void
{
    // Trigger a warning - will be converted to ErrorException
    trigger_error('This is a triggered warning', E_USER_WARNING);
    echo '<p>Warning triggered! Check the error handler output.</p>';
}

function handleException(): void
{
    // Throw an exception - will be caught by exception handler
    throw new RuntimeException('This is a test exception');
}

function handleFatal(): void
{
    // Call undefined function to trigger fatal error
    // Will be caught by shutdown handler
    undefinedFunction();
}

function handleNotFound(): void
{
    http_response_code(404);
    echo '<h1>404 Not Found</h1>';
    echo '<p><a href="/">Go back home</a></p>';
}
```

**What's happening here:**
1. **Error Handler Registration** (line 10): Creates and registers `DetailedErrorHandler` in one line using method chaining
2. **Simple Routing** (lines 12-20): Uses PHP 8 `match` expression for clean routing without a full routing system
3. **Error Conversion Demo** (`handleError`): Shows how `trigger_error()` is converted to an exception
4. **Exception Handling Demo** (`handleException`): Demonstrates catching thrown exceptions
5. **Fatal Error Demo** (`handleFatal`): Shows how even fatal errors are caught during shutdown
6. **HTTP Status Codes**: Proper status codes are set (500 for errors, 404 for not found)

### Running the Demo

```bash
cd framework/demo-app
php8.5 -S localhost:8000 -t public
```

Then visit:
- `http://localhost:8000/` - Home page with links
- `http://localhost:8000/error` - See error-to-exception conversion
- `http://localhost:8000/exception` - See exception handling
- `http://localhost:8000/fatal` - See fatal error handling

**Expected output:**
When visiting `/exception`, you'll see a formatted error page with:
```
💥 RuntimeException

This is a test exception

📁 /path/to/demo-app/public/index.php:42

📚 Backtrace (PHP 8.5):
[full stack trace here]
```

### Key Takeaways

- **Simple Registration**: One-line setup with `new DetailedErrorHandler()->register()`
- **Comprehensive Coverage**: Handles exceptions, errors, and fatal errors in one system
- **Developer Experience**: Clear, readable error output makes debugging faster
- **Production Ready**: Although styled for development, the architecture supports different formatters for production (JSON, plain text, etc.)
- **No Dependencies**: Built entirely from scratch using only PHP 8.5 features

---

📚 **Learn More:** This implementation is explained in detail with step-by-step tutorials, tests, and best practices at [masterphp.eu](https://masterphp.eu)
