# Chapter 21: Console Error Handler - Interactive Debugging in Terminal

> Part of the Larafony Framework - A modern PHP 8.5 framework built from scratch
>
> 📚 Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)

---

## 🎯 Probably the Only One!

**The only PHP framework with native, built-from-scratch interactive error debugging in the console!**

While other frameworks like Laravel rely on external tools like PsySH for interactive console debugging, Larafony implements its own sophisticated debug session system. When an exception occurs in your console commands, you don't just get a stack trace and exit—you get an **interactive REPL-like experience** where you can explore frames, inspect variables, view source code, and understand the error context without leaving your terminal or installing external dependencies.

This is the same beautiful, web-like debugging experience you get in browsers... **but available in your console!**

---

## Overview

This chapter implements a sophisticated console error handler that brings an interactive debugging experience to CLI applications. When an exception occurs in Artisan-like commands, developers get a beautiful, color-coded stack trace with an interactive debug session that allows them to explore the error context, view variables, navigate frames, and understand the issue without leaving the terminal.

The implementation follows a clean separation of concerns with a base handler abstraction, specialized console renderer, and an interactive debug session. The system automatically detects whether the application is running in CLI or web mode and registers the appropriate error handler. This provides a unified error handling experience across both web and console environments while respecting the unique needs of each context.

The console error handler converts PHP errors into exceptions, catches fatal errors during shutdown, and presents them in a developer-friendly format with syntax-highlighted code snippets, colorful output, and interactive commands for deep inspection of the error context.

## Key Components

### Core Error Handling

- **BaseHandler** - Abstract base class for all error handlers, providing registration of PHP error/exception handlers and fatal error detection
- **ConsoleHandler** - Console-specific error handler that renders exceptions through ConsoleRenderer with graceful fallback on critical errors
- **FatalError** - Exception class representing fatal PHP errors (E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR)

### Console Rendering System

- **ConsoleRenderer** - Main console rendering coordinator that creates interactive debug sessions with header rendering and command processing
- **ConsoleRendererFactory** - Factory pattern implementation for constructing the complex console renderer dependency graph with all required helpers and partials
- **DebugSession** - Interactive debug loop that prompts users for commands and processes them to explore exception context

### Rendering Helpers

Helper classes supporting the rendering system:
- **PathNormalizer** - Normalizes file paths for cleaner display
- **TraceFrameFetcher** - Fetches and validates stack trace frames
- **VariableFormatter** - Formats PHP variables for readable console output

### Rendering Partials

Modular rendering components following single responsibility:
- **ConsoleHeaderRenderer** - Renders exception header with error message and basic info
- **ConsoleTraceRenderer** - Renders full stack trace listing
- **ConsoleFrameRenderer** - Coordinates frame detail, variable, and source rendering
- **ConsoleEnvironmentRenderer** - Displays environment information (PHP version, memory, etc.)
- **ConsoleHelpRenderer** - Shows available debug commands
- **ConsoleCommandProcessor** - Processes user commands in the debug session
- **CodeSnippetRenderer** - Renders code snippets with syntax highlighting

### Frame Rendering

Specialized frame renderers (all extend BaseFrameRenderer):
- **FrameDetailsRenderer** - Shows detailed frame information (file, line, function, class)
- **FrameSourceRenderer** - Displays source code around the error line
- **FrameVariablesRenderer** - Shows variables available in the frame scope

### Service Integration

- **ErrorHandlerServiceProvider** (modified) - Now detects CLI vs web mode using `php_sapi_name()` and registers ConsoleHandler for CLI or DetailedErrorHandler for web

## New Features

### Interactive Debug Session

When an exception occurs in console commands, the handler drops into an interactive debug mode where developers can:

- Type `help` to see available commands
- Type `trace` to view the full stack trace
- Type `frame N` to inspect a specific frame in detail
- Type `vars` to see variables in the current frame
- Type `source` to view source code around the error
- Type `env` to check environment information
- Type `exit` to quit the debug session

### Automatic Environment Detection

The ErrorHandlerServiceProvider now automatically detects the runtime environment:

```php
$isConsole = php_sapi_name() === 'cli';
```

This ensures the correct handler is registered without manual configuration.

### Fatal Error Recovery

The BaseHandler catches fatal errors during PHP's shutdown phase and converts them into handleable exceptions:

- E_ERROR
- E_PARSE
- E_CORE_ERROR
- E_COMPILE_ERROR
- E_USER_ERROR
- E_USER_DEPRECATED

## Usage Examples

### Basic Usage

Error handling is automatically registered when the application boots. No manual setup required:

```php
<?php

// In bootstrap/console.php - handlers register automatically
$app->withServiceProviders([
    HttpServiceProvider::class,
    ConfigServiceProvider::class,
    ConsoleServiceProvider::class, // Must come before ErrorHandlerServiceProvider
    DatabaseServiceProvider::class,
    ErrorHandlerServiceProvider::class, // Automatically detects CLI mode
]);
```

### Console Command with Exception

```php
<?php

namespace App\Commands;

use Larafony\Framework\Console\Attributes\Command;
use Larafony\Framework\Console\Contracts\CommandContract;
use Larafony\Framework\Console\Contracts\OutputContract;

#[Command(name: 'process:data', description: 'Process application data')]
class ProcessDataCommand implements CommandContract
{
    public function __construct(private OutputContract $output)
    {
    }

    public function handle(array $options = []): int
    {
        $this->output->info('Processing data...');

        // If an exception occurs, interactive debug session starts
        $data = $this->processComplexData();

        $this->output->success('Data processed successfully!');
        return 0;
    }

    private function processComplexData(): array
    {
        // This will trigger the console error handler
        throw new \RuntimeException('Data processing failed: Invalid format');
    }
}
```

### Interactive Debug Session Example

When the exception occurs, you'll see:

```
[ERROR] RuntimeException
Data processing failed: Invalid format

in /var/www/app/Commands/ProcessDataCommand.php:24

Debug mode: (type 'help' for available commands) > help

Available commands:
  trace    - Show full stack trace
  frame N  - Show details for frame N
  vars     - Show variables in current frame
  source   - Show source code around error
  env      - Show environment information
  exit     - Exit debug mode

Debug mode: (type 'help' for available commands) > trace

Stack trace:
  #0 ProcessDataCommand->processComplexData()
     at /var/www/app/Commands/ProcessDataCommand.php:24

  #1 ProcessDataCommand->handle()
     at /var/www/app/Commands/ProcessDataCommand.php:15

  #2 Kernel->handleCommand()
     at /var/www/framework/src/Console/Kernel.php:42

Debug mode: (type 'help' for available commands) > frame 0

Frame #0: ProcessDataCommand->processComplexData()
File: /var/www/app/Commands/ProcessDataCommand.php:24

Source code:
  20 │
  21 │     private function processComplexData(): array
  22 │     {
  23 │         // This will trigger the console error handler
> 24 │         throw new \RuntimeException('Data processing failed: Invalid format');
  25 │     }
  26 │ }

Debug mode: (type 'help' for available commands) > exit
```

### Error Handler Registration Internals

The ErrorHandlerServiceProvider handles both environments:

```php
<?php

public function register(ContainerContract $container): self
{
    $isConsole = php_sapi_name() === 'cli';

    if ($isConsole) {
        // Register console error handler with factory
        $factory = new ConsoleRendererFactory($container);
        $renderer = $factory->create();

        $handler = new ConsoleHandler(
            $renderer,
            fn(int $exitCode) => exit($exitCode)
        );

        $container->set(BaseHandler::class, $handler);
        $container->set(ConsoleHandler::class, $handler);
    } else {
        // Register web error handler (from previous chapters)
        $viewManager = $container->get(ViewManager::class);
        $handler = new DetailedErrorHandler($viewManager, $debug);

        $container->set(BaseHandler::class, $handler);
        $container->set(DetailedErrorHandler::class, $handler);
    }

    return $this;
}

public function boot(ContainerContract $container): void
{
    // Boot the appropriate handler
    $handler = $container->get(BaseHandler::class);
    $handler->register(); // Registers PHP error/exception/shutdown handlers
}
```

## Comparison with Other Frameworks

| Feature | Larafony | Laravel | Symfony |
|---------|----------|---------|---------|
| **Console Error Display** | Interactive debug session with commands | renderForConsole() method in exception handler | Improved in 7.4 - clean stack traces |
| **Environment Detection** | Automatic via php_sapi_name() | Manual context passing | Automatic via runtime detection |
| **Interactive Debugging** | Built-in debug session loop | Not available by default | Not available by default |
| **Error Conversion** | Converts all errors to exceptions | Converts errors to exceptions | ErrorHandler component |
| **Fatal Error Handling** | Shutdown function catches fatals | Shutdown handler in ExceptionHandler | ErrorHandler with DebugClassLoader |
| **Code Snippets** | Color-coded with line numbers | Basic text output | HTML in terminal (fixed in 7.4) |
| **Stack Trace Navigation** | Interactive frame inspection | Static trace output | Static trace with verbosity levels |
| **Variable Inspection** | Per-frame variable display | Not available in console | Not available in console |
| **Factory Pattern** | ConsoleRendererFactory for DI | Service container auto-wiring | Service container with tags |
| **Base Handler** | Abstract BaseHandler for both web/CLI | Single Handler class with render methods | Separate error handler component |

**Key Differences:**

- **Interactive Experience**: Larafony provides a true interactive debugging experience in the terminal, similar to a debugger REPL. Laravel and Symfony output the error and exit, requiring developers to re-run with different options or add debugging code.

- **Automatic Environment Detection**: Larafony automatically detects CLI vs web mode and registers the appropriate handler. Laravel requires manual configuration in the Handler class, and Symfony uses a separate error-handler component.

- **Clean Architecture**: Larafony uses a base handler abstraction with specialized implementations (ConsoleHandler, DetailedErrorHandler) rather than a single class with conditional logic.

- **Factory Pattern**: The ConsoleRendererFactory centralizes the complex dependency graph construction, making it easier to test and maintain. Laravel relies on container auto-wiring, and Symfony uses service configuration.

- **Symfony 7.4 Improvements**: Symfony recently (2024) fixed the long-standing issue of HTML flooding the terminal, now providing clean stack traces similar to Larafony's approach.

- **No External Dependencies**: Larafony builds the console error handler from scratch without external packages, while Symfony has a dedicated `symfony/error-handler` component, and Laravel integrates error handling into the framework core.

---

📚 **Learn More:** This implementation is explained in detail with step-by-step tutorials, tests, and best practices at [masterphp.eu](https://masterphp.eu)
