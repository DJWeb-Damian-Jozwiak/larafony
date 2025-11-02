# Chapter 20: Error Handling & Exception Management

> Part of the Larafony Framework - A modern PHP 8.5 framework built from scratch
>
> 📚 Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)

## Overview

This chapter implements a comprehensive error handling system for the Larafony framework, providing both developer-friendly debug views and polished production error pages. The implementation follows PHP's native exception handling mechanisms with `set_exception_handler()` and `set_error_handler()`, ensuring all uncaught exceptions and errors are properly captured and rendered through the framework's Blade templating engine.

The error handler supports environment-aware rendering, automatically displaying detailed backtraces with interactive code snippets in debug mode (APP_DEBUG=true) or clean, user-friendly error pages in production (APP_DEBUG=false). The system distinguishes between different error types (404 vs 500) and provides customizable Blade views for each scenario.

Key features include structured backtrace generation with code context, VS Code-inspired dark theme debug interface with clickable stack frames, graceful fallback HTML rendering if view rendering fails, and fatal error handling through shutdown functions. The architecture uses PHP 8.5's property hooks (`private(set)`) for immutable public access to trace data and readonly classes for value objects.

## Key Components

### Core Error Handler

- **DetailedErrorHandler** - Main error handler implementing the ErrorHandler contract. Captures exceptions via `set_exception_handler()`, converts PHP errors to ErrorException via `set_error_handler()`, handles fatal errors through `register_shutdown_function()`, renders debug or production views based on APP_DEBUG flag, and provides fallback HTML if Blade rendering fails.

### Backtrace Generation

- **Backtrace** - Factory for generating TraceCollection from Throwable exceptions
- **TraceCollection** - Collection of TraceFrame objects with property hooks for immutable access (`public private(set) array $frames`)
- **TraceFrame** - Readonly value object representing a single stack frame (includes file, line, class, function, args, and CodeSnippet)
- **CodeSnippet** - Extracts and formats code context around error lines (10 lines before/after by default, uses property hooks for public read-only access to processed line data)

### Service Integration

- **ErrorHandlerServiceProvider** - Registers DetailedErrorHandler with ViewManager dependency, reads APP_DEBUG from environment via EnvReader, boots error handler by calling `register()` method

### Contracts

- **ErrorHandler** - Interface defining `handle(Throwable): void` and `register(): void` methods

## PSR Standards Implemented

- **PSR-3**: Logging - While not directly implemented in this chapter, the error handler is designed to integrate with PSR-3 loggers for error reporting in future chapters
- **PSR-11**: Container - Uses framework's PSR-11 container for dependency injection of ViewManager and service provider registration

The error handling system is built to be extensible for future PSR-3 logging integration and PSR-14 event dispatching for exception handling hooks.

## New Attributes

No new attributes were introduced in this chapter. The error handling system works with the framework's existing attribute-based routing and view systems.

## Usage Examples

### Basic Error Handling Setup

```php
<?php

use Larafony\Framework\ErrorHandler\ServiceProviders\ErrorHandlerServiceProvider;
use Larafony\Framework\View\ServiceProviders\ViewServiceProvider;

// In bootstrap/app.php
$app->withServiceProviders([
    // ... other providers
    ViewServiceProvider::class,
    ErrorHandlerServiceProvider::class, // Must be AFTER ViewServiceProvider
]);
```

**Important:** ErrorHandlerServiceProvider must be registered AFTER ViewServiceProvider because it depends on ViewManager being available in the container.

### Environment Configuration

```env
# .env file
APP_DEBUG=true  # Show detailed debug traces
# or
APP_DEBUG=false # Show user-friendly error pages
```

### Handling 404 Errors

```php
<?php

use Larafony\Framework\Core\Exceptions\NotFoundError;

// In your controller or model
public function findForRoute(string|int $value): static
{
    $result = self::query()->where('id', '=', $value)->first();

    if ($result === null) {
        throw new NotFoundError(
            sprintf('Model %s with id %s not found', static::class, $value)
        );
    }

    return $result;
}
```

When NotFoundError is thrown, the error handler automatically renders the `errors.404` view with a 404 status code.

### Custom Error Views

Create these Blade views in your application's `resources/views/blade/errors/` directory:

**404.blade.php** - Rendered for NotFoundError exceptions:
```php
<!DOCTYPE html>
<html lang="en">
<head>
    <title>404 - Page Not Found</title>
</head>
<body>
    <h1>Page Not Found</h1>
    <p>The page you're looking for doesn't exist.</p>
</body>
</html>
```

**500.blade.php** - Rendered for all other exceptions in production:
```php
<!DOCTYPE html>
<html lang="en">
<head>
    <title>500 - Internal Server Error</title>
</head>
<body>
    <h1>Internal Server Error</h1>
    <p>Something went wrong on our end.</p>
</body>
</html>
```

**debug.blade.php** - Rendered in debug mode with full backtrace:
```php
<!DOCTYPE html>
<html lang="en">
<head>
    <title>{{ $exception['class'] }} | Debug</title>
</head>
<body>
    <h1>{{ $exception['class'] }}</h1>
    <p>{{ $exception['message'] }}</p>
    <p>{{ $exception['file'] }}:{{ $exception['line'] }}</p>

    @foreach($backtrace as $frame)
        <div class="frame">
            <strong>{{ $frame['class'] }}::{{ $frame['function'] }}()</strong>
            <code>{{ $frame['file'] }}:{{ $frame['line'] }}</code>

            @foreach($frame['snippet']['lines'] as $lineNum => $lineContent)
                <pre>{{ $lineNum }}: {{ $lineContent }}</pre>
            @endforeach
        </div>
    @endforeach
</body>
</html>
```

### Advanced Example - Custom Exception Handling

```php
<?php

namespace App\Exceptions;

use Larafony\Framework\Core\Exceptions\NotFoundError;

class ResourceNotFoundException extends NotFoundError
{
    public function __construct(string $resource, string|int $id)
    {
        parent::__construct(
            sprintf('Resource "%s" with ID "%s" was not found', $resource, $id)
        );
    }
}

// In your application code
throw new ResourceNotFoundException('User', 42);
```

This will automatically be caught by DetailedErrorHandler and rendered as a 404 error with the custom message.

### Programmatic Backtrace Generation

```php
<?php

use Larafony\Framework\ErrorHandler\Backtrace;
use Larafony\Framework\ErrorHandler\TraceCollection;

$backtrace = new Backtrace();

try {
    // Some risky code
    throw new \RuntimeException('Something went wrong');
} catch (\Throwable $e) {
    $trace = $backtrace->generate($e);

    // Access frames
    foreach ($trace->frames as $frame) {
        echo $frame->file . ':' . $frame->line . PHP_EOL;
        echo $frame->class . '::' . $frame->function . '()' . PHP_EOL;

        // Access code snippet
        foreach ($frame->snippet->lines as $lineNum => $lineContent) {
            echo $lineNum . ': ' . $lineContent . PHP_EOL;
        }
    }
}
```

## Implementation Details

### DetailedErrorHandler

**Location:** `src/Larafony/ErrorHandler/DetailedErrorHandler.php`

**Purpose:** Main error handler that captures all uncaught exceptions and PHP errors, rendering them through Blade views based on debug mode.

**Key Methods:**
- `handle(Throwable $throwable): void` - Processes exceptions, determines status code, and renders appropriate view
- `register(): void` - Registers exception handler, error handler, and shutdown function with PHP
- `renderDebugView(Throwable $exception): string` - Generates detailed backtrace and renders debug.blade.php
- `renderProductionView(int $statusCode): string` - Renders user-friendly error pages (404.blade.php or 500.blade.php)
- `getStatusCode(Throwable $exception): int` - Maps exception types to HTTP status codes (NotFoundError → 404, others → 500)
- `renderFallback(int $statusCode, Throwable $original, Throwable $renderError): string` - Provides HTML fallback if Blade rendering fails

**Dependencies:**
- `ViewManager` - For rendering Blade templates
- `bool $debug` - Flag from APP_DEBUG environment variable

**Usage:**
```php
$viewManager = $container->get(ViewManager::class);
$debug = EnvReader::read('APP_DEBUG', 'false') === 'true';

$errorHandler = new DetailedErrorHandler($viewManager, $debug);
$errorHandler->register(); // Activates error handling
```

### TraceCollection & TraceFrame

**Location:** `src/Larafony/ErrorHandler/TraceCollection.php`, `src/Larafony/ErrorHandler/TraceFrame.php`

**Purpose:** Structured representation of exception backtraces with immutable public access.

**Key Features:**
- PHP 8.5 property hooks: `public private(set) array $frames` allows public read access but private write access
- `TraceFrame` is a readonly class ensuring immutability
- `fromThrowable()` factory method builds collection from exception, automatically adding exception's file/line as first frame

**Usage:**
```php
$trace = TraceCollection::fromThrowable($exception);

// Frames are publicly readable but cannot be modified
foreach ($trace->frames as $frame) {
    echo $frame->file; // readonly property
    echo $frame->line;
    echo $frame->class;
}
```

### CodeSnippet

**Location:** `src/Larafony/ErrorHandler/CodeSnippet.php`

**Purpose:** Extracts code context around error lines for display in debug views.

**Key Features:**
- Reads source file and extracts lines around error (default: 10 lines before/after)
- Property hooks for immutable public access: `public private(set) array $lines`
- Handles non-existent files gracefully (empty snippets)
- Returns associative array with line numbers as keys

**Usage:**
```php
$snippet = new CodeSnippet('/path/to/file.php', 42, contextLines: 5);

// Access extracted lines
foreach ($snippet->lines as $lineNum => $lineContent) {
    if ($lineNum === $snippet->errorLine) {
        echo ">>> ERROR: ";
    }
    echo "$lineNum: $lineContent\n";
}
```

### ErrorHandlerServiceProvider

**Location:** `src/Larafony/ErrorHandler/ServiceProviders/ErrorHandlerServiceProvider.php`

**Purpose:** Integrates error handler into framework's service container and lifecycle.

**Key Features:**
- Reads APP_DEBUG from environment via EnvReader
- Resolves ViewManager from container before creating error handler
- Registers error handler in `register()` method
- Activates error handler in `boot()` method

**Usage:**
```php
// Automatically used when added to bootstrap/app.php
$app->withServiceProviders([
    ViewServiceProvider::class,      // Must come first
    ErrorHandlerServiceProvider::class, // Depends on ViewManager
]);
```

## Comparison with Other Frameworks

| Feature | Larafony | Laravel | Symfony |
|---------|----------|---------|---------|
| Registration Method | `set_exception_handler()` in service provider boot | `withExceptions()` in bootstrap/app.php (Laravel 12+) | Event listener on `kernel.exception` event |
| Configuration | Attribute-based routing + ENV (APP_DEBUG) | Config files + ENV (APP_DEBUG) | YAML/PHP config + ENV (APP_ENV) |
| Error Views | Blade templates in `resources/views/blade/errors/` | Blade templates in `resources/views/errors/` | Twig templates in `templates/bundles/TwigBundle/Exception/` |
| Debug Mode | Single DetailedErrorHandler with debug flag | Whoops library (debug) vs. custom views (production) | Symfony Profiler + exception page (debug) vs. error controller (prod) |
| Status Code Mapping | Exception instance matching (NotFoundError → 404) | Custom exception `getStatusCode()` method | Exception listener checks exception type |
| Backtrace | Custom TraceCollection with CodeSnippet | Whoops pretty page handler with code highlighting | VarDumper component with stack trace |
| PSR Compliance | PSR-11 (Container), designed for PSR-3 (Logging) | PSR-3 (Logging), PSR-11 (Container), PSR-15 (Middleware) | PSR-3, PSR-4, PSR-6, PSR-7, PSR-11, PSR-15, PSR-18 |
| Approach | Native PHP exception handlers + Blade | Laravel exceptions + middleware + views | Event-driven with ExceptionListener |
| Fallback Rendering | Built-in HTML fallback if view fails | Falls back to plain text in worst case | Falls back to raw exception output |
| Fatal Error Handling | `register_shutdown_function()` catches fatal errors | Same approach with custom formatting | Symfony handles at kernel level |

**Key Differences:**

- **Larafony** uses PHP's native exception handlers directly (`set_exception_handler`) for simplicity and transparency, avoiding middleware complexity for error handling
- **Laravel 12** uses a configuration-based approach with `withExceptions()` method in bootstrap/app.php, providing a centralized exception management API
- **Symfony** uses event-driven architecture with `kernel.exception` event, allowing multiple listeners to process exceptions in order of priority
- **Larafony's Blade integration** renders errors through the same view system as regular responses, ensuring consistency and allowing full Blade directive support in error views
- **Property hooks (PHP 8.5)** in Larafony provide immutable public access to trace data without getter methods, a modern approach not yet available in Laravel/Symfony
- **Service provider order matters** in Larafony - ErrorHandlerServiceProvider must load after ViewServiceProvider, making dependencies explicit in configuration
- **Debug vs. Production rendering** in Larafony uses a single handler class with conditional logic, while Laravel/Symfony use separate handlers/listeners for different environments

## Real World Integration

This chapter's features are demonstrated in both the demo application and larafony.com website with beautiful, production-ready error pages.

### Demo Application Changes

The demo application includes three Blade error views that showcase the framework's error handling capabilities:

1. **404.blade.php** - Elegant "Page Not Found" with animated starfield background
2. **500.blade.php** - Professional "Internal Server Error" with matching design
3. **debug.blade.php** - Interactive VS Code-inspired debug interface with clickable stack frames

### File Structure
```
demo-app/
├── bootstrap/
│   └── app.php                          # ErrorHandlerServiceProvider moved to END
├── resources/views/blade/errors/
│   ├── 404.blade.php                   # Purple gradient, animated stars, home/back buttons
│   ├── 500.blade.php                   # Blue gradient, consistent styling with 404
│   └── debug.blade.php                  # Dark theme, interactive backtrace viewer
└── .env                                 # APP_DEBUG=true for development

larafony.com/
├── bootstrap/
│   └── app.php                          # ErrorHandlerServiceProvider moved to END
└── resources/views/blade/errors/
    ├── 404.blade.php                   # Identical styling to demo-app
    ├── 500.blade.php                   # Identical styling to demo-app
    └── debug.blade.php                  # Identical functionality to demo-app
```

### Implementation Example

**File: `demo-app/resources/views/blade/errors/debug.blade.php`**

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $exception['class'] }} | Larafony Debug</title>
    <style>
        /* VS Code Dark Theme Styling */
        body {
            font-family: 'Menlo', 'Monaco', 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            font-size: 14px;
            line-height: 1.6;
        }

        .header {
            background: #252526;
            border-bottom: 1px solid #3e3e42;
            padding: 1.5rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .exception-message {
            font-size: 1.25rem;
            color: #f48771; /* Error red */
            font-weight: 600;
        }

        .container {
            display: flex;
            height: calc(100vh - 120px);
        }

        /* Sidebar with stack frames */
        .sidebar {
            width: 400px;
            background: #252526;
            border-right: 1px solid #3e3e42;
            overflow-y: auto;
        }

        .frame {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #3e3e42;
            cursor: pointer;
            transition: background 0.2s;
        }

        .frame:hover {
            background: #2d2d30;
        }

        .frame.active {
            background: #37373d;
            border-left: 3px solid #007acc; /* VS Code blue */
        }

        /* Code display area */
        .content {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
        }

        .code-line.error-line {
            background: rgba(244, 135, 113, 0.1);
            border-left: 3px solid #f48771;
        }

        .line-number {
            color: #858585;
            text-align: right;
            padding: 0 1rem;
            user-select: none;
            min-width: 60px;
        }
    </style>
</head>
<body>
    <!-- Header with exception details -->
    <div class="header">
        <div class="exception-class">{{ $exception['class'] }}</div>
        <div class="exception-message">{{ $exception['message'] ?: 'No message' }}</div>
        <div class="exception-location">
            in <span class="file">{{ $exception['file'] }}</span>
            at line <span class="line">{{ $exception['line'] }}</span>
        </div>
    </div>

    <div class="container">
        <!-- Sidebar: List of stack frames -->
        <div class="sidebar">
            @foreach($backtrace as $index => $frame)
            <div class="frame {{ $index === 0 ? 'active' : '' }}" data-frame="{{ $index }}">
                <div class="frame-function">
                    @if($frame['class'])
                        <span class="frame-class">{{ $frame['class'] }}</span>::
                    @endif
                    {{ $frame['function'] }}()
                </div>
                <div class="frame-location">
                    <span class="frame-file">{{ basename($frame['file']) }}</span>:<span class="frame-line">{{ $frame['line'] }}</span>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Content: Code snippets for each frame -->
        <div class="content" id="content">
            @foreach($backtrace as $index => $frame)
            <div class="frame-content" id="frame-{{ $index }}" style="{{ $index === 0 ? '' : 'display: none;' }}">
                <div class="code-snippet">
                    <div class="snippet-header">
                        {{ $frame['file'] }}
                        @if($frame['class'])
                            <span class="badge">{{ basename(str_replace('\\', '/', $frame['class'])) }}</span>
                        @endif
                    </div>
                    <div class="snippet-body">
                        @if(!empty($frame['snippet']['lines']))
                            @foreach($frame['snippet']['lines'] as $lineNum => $lineContent)
                            <div class="code-line {{ $lineNum == $frame['snippet']['errorLine'] ? 'error-line' : '' }}">
                                <div class="line-number">{{ $lineNum }}</div>
                                <div class="line-content">{{ $lineContent }}</div>
                            </div>
                            @endforeach
                        @else
                            <div class="no-snippet">No source code available</div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <script>
        // Interactive frame navigation
        // Click on a stack frame in sidebar to view its code
        document.querySelectorAll('.frame').forEach(frame => {
            frame.addEventListener('click', function() {
                const frameIndex = this.dataset.frame;

                // Update active frame in sidebar
                document.querySelectorAll('.frame').forEach(f => f.classList.remove('active'));
                this.classList.add('active');

                // Show corresponding code snippet
                document.querySelectorAll('.frame-content').forEach(c => c.style.display = 'none');
                document.getElementById('frame-' + frameIndex).style.display = 'block';
            });
        });
    </script>
</body>
</html>
```

**What's happening here:**

1. **Header Section** - Displays exception class, message, and location using data from `$exception` array passed by DetailedErrorHandler
2. **Two-Column Layout** - Sidebar shows clickable stack frames, main content area shows code snippets
3. **Blade Loops** - `@foreach($backtrace as $index => $frame)` iterates through TraceFrame data structures
4. **Code Snippet Display** - Each frame includes 10 lines before/after error, with error line highlighted in red
5. **Interactive JavaScript** - Clicking a frame in sidebar switches the displayed code snippet using data-frame attribute
6. **VS Code Theme** - Color scheme matches VS Code dark theme (#1e1e1e background, #f48771 error red, #007acc blue accents)
7. **Conditional Rendering** - Only the first frame (index 0) is shown by default, others hidden with `display: none`

**File: `demo-app/resources/views/blade/errors/404.blade.php`**

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | Larafony</title>
    <style>
        body {
            /* Purple gradient background with animated stars */
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Animated starfield effect */
        .star {
            position: absolute;
            width: 2px;
            height: 2px;
            background: white;
            border-radius: 50%;
            animation: twinkle 3s infinite;
        }

        @keyframes twinkle {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 1; }
        }

        .error-code {
            font-size: 8rem;
            font-weight: 900;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
    </style>
</head>
<body>
    <!-- Generate 100 random stars with JavaScript -->
    <div class="stars" id="stars"></div>

    <div class="container">
        <div class="error-box">
            <div class="error-code">404</div>
            <h1 class="error-title">Page Not Found</h1>
            <p class="error-message">
                Oops! The page you're looking for doesn't exist.
                It might have been moved or deleted.
            </p>

            <div class="buttons">
                <a href="/" class="btn btn-primary">
                    <span class="icon">🏠</span>
                    <span>Go Home</span>
                </a>
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <span class="icon">←</span>
                    <span>Go Back</span>
                </a>
            </div>

            <div class="footer">
                Powered by <span class="larafony-logo">Larafony</span> Framework
            </div>
        </div>
    </div>

    <script>
        // Generate 100 stars at random positions with random animation delays
        const starsContainer = document.getElementById('stars');
        for (let i = 0; i < 100; i++) {
            const star = document.createElement('div');
            star.className = 'star';
            star.style.left = Math.random() * 100 + '%';
            star.style.top = Math.random() * 100 + '%';
            star.style.animationDelay = Math.random() * 3 + 's';
            star.style.animationDuration = (Math.random() * 2 + 2) + 's';
            starsContainer.appendChild(star);
        }
    </script>
</body>
</html>
```

**What's happening here:**

1. **Gradient Background** - Uses CSS linear-gradient from purple (#667eea) to darker purple (#764ba2) at 135° angle
2. **Starfield Animation** - JavaScript generates 100 star divs with random positions and staggered twinkle animations
3. **Pulsing 404 Text** - Large gradient text with CSS animation that scales from 1.0 to 1.05 and back
4. **User Actions** - Home button returns to root, Back button uses browser history
5. **Responsive Design** - Flexbox centering ensures error box is always centered regardless of screen size
6. **Framework Branding** - Footer shows "Powered by Larafony Framework" with gradient text
7. **No Framework Dependencies** - Pure HTML, CSS, and vanilla JavaScript for reliability even if framework fails

**File: `demo-app/bootstrap/app.php`**

```php
<?php

declare(strict_types=1);

use Larafony\Framework\Config\ServiceProviders\ConfigServiceProvider;
use Larafony\Framework\Database\ServiceProviders\DatabaseServiceProvider;
use Larafony\Framework\ErrorHandler\ServiceProviders\ErrorHandlerServiceProvider;
use Larafony\Framework\Http\ServiceProviders\HttpServiceProvider;
use Larafony\Framework\Routing\ServiceProviders\RouteServiceProvider;
use Larafony\Framework\View\ServiceProviders\ViewServiceProvider;
use Larafony\Framework\Web\ServiceProviders\WebServiceProvider;

require_once __DIR__ . '/../vendor/autoload.php';

$app = \Larafony\Framework\Web\Application::instance(base_path: dirname(__DIR__));

// IMPORTANT: Order matters! ErrorHandlerServiceProvider MUST be last
// because it depends on ViewManager being registered by ViewServiceProvider
$app->withServiceProviders([
    ConfigServiceProvider::class,
    DatabaseServiceProvider::class,
    HttpServiceProvider::class,
    RouteServiceProvider::class,
    ViewServiceProvider::class,
    WebServiceProvider::class,
    ErrorHandlerServiceProvider::class, // Must be last to ensure ViewManager is available
]);

$app->withRoutes(function ($router) {
    $router->loadAttributeRoutes(__DIR__ . '/../src/Controllers');
});

return $app;
```

**What's happening here:**

1. **Service Provider Order** - ErrorHandlerServiceProvider placed at end of array to ensure all dependencies are available
2. **ViewManager Dependency** - ErrorHandlerServiceProvider needs ViewManager from ViewServiceProvider to render Blade error views
3. **Framework Bootstrap** - Application instance created with base path, providers registered, routes loaded
4. **Comment Explanation** - Clear documentation of why order matters, helping future developers understand the constraint

### Running the Demo

```bash
# Create a new Larafony project from skeleton
composer create-project larafony/skeleton my-larafony-app

# Navigate to project directory
cd my-larafony-app

# Set environment to debug mode
echo "APP_DEBUG=true" >> .env

# Start development server
php -S localhost:8000 -t public

# Test 404 error
curl http://localhost:8000/nonexistent-page

# Test 500 error (visit invalid model ID)
curl http://localhost:8000/notes/999999

# Switch to production mode
sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env

# Test production error pages
curl http://localhost:8000/nonexistent-page
```

**Alternative:** Clone from GitHub
```bash
git clone https://github.com/larafony/skeleton.git my-larafony-app
cd my-larafony-app
composer install
```

**Expected output:**

**Debug Mode (APP_DEBUG=true):**
- 404: Shows debug.blade.php with full backtrace, NotFoundError at top of stack, clickable frames
- 500: Shows debug.blade.php with exception details, code snippets with line numbers, VS Code theme

**Production Mode (APP_DEBUG=false):**
- 404: Shows clean 404.blade.php with purple gradient, stars, "Go Home" button
- 500: Shows clean 500.blade.php with blue gradient, generic error message, no technical details

### Key Takeaways

- **Error views are application-level** - Each app (demo-app, larafony.com) has its own error views in `resources/views/blade/errors/`, allowing customization per project
- **Framework provides the handler** - DetailedErrorHandler lives in framework core and handles all the exception catching and rendering logic
- **Service provider order matters** - ErrorHandlerServiceProvider depends on ViewServiceProvider, making dependency order explicit in bootstrap/app.php
- **Environment-aware rendering** - Single error handler switches between debug and production views based on APP_DEBUG flag
- **Graceful degradation** - If Blade view rendering fails, `renderFallback()` provides basic HTML to ensure errors are always visible
- **Modern PHP features** - Uses PHP 8.5 property hooks (`private(set)`), readonly classes, and first-class callables (`$this->handle(...)`)
- **VS Code-inspired debug UI** - Interactive backtrace viewer with clickable frames makes debugging feel like using an IDE
- **Consistent design language** - 404 and 500 production pages share the same gradient theme and animation style for brand consistency
- **No middleware complexity** - Unlike PSR-15 middleware approach, error handling uses PHP's native exception handlers for simplicity and reliability

---

📚 **Learn More:** This implementation is explained in detail with step-by-step tutorials, tests, and best practices at [masterphp.eu](https://masterphp.eu)
