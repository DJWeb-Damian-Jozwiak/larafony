# Chapter 9: Console Kernel and Attribute-Based Commands

> Part of the Larafony Framework - A modern PHP 8.5 framework built from scratch
>
> 📚 Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)

## Overview

Chapter 9 introduces a complete console application layer to the Larafony framework, featuring an attribute-based command system that leverages PHP 8.5's advanced features. This implementation provides a modern, type-safe approach to building CLI applications with automatic command discovery, argument/option parsing, and colored output formatting.

The console system is built entirely from scratch without dependencies on Symfony Console or other packages. It features automatic command registration through attributes, property-based argument injection using reflection, and a flexible output formatting system with predefined styles. Commands are discovered automatically from the filesystem, registered in a central registry, and resolved through the dependency injection container.

This chapter demonstrates how PHP 8.5's `protected(set)` property hooks and attributes can create an elegant, declarative API for CLI applications that rivals established frameworks while maintaining complete control over implementation details.

## Key Components

### Core Console Architecture

- **Application** - Console application singleton extending Container, manages lifecycle and command handling
- **Kernel** - Orchestrates command discovery, resolution, and execution; handles argument parsing and routing
- **Command** - Abstract base class for all console commands with `protected(set) OutputContract $output` using PHP 8.5 property hooks
- **CommandRegistry** - Central registry storing command name-to-class mappings, populated by service providers and discovery

### Command Discovery and Resolution

- **CommandDiscovery** - Scans filesystem for command classes, reads attributes, builds command map (with helper: FileToClassNameConverter for path-to-FQCN conversion)
- **CommandResolver** - Resolves command instance from registry, applies arguments/options to properties via reflection

### Attribute System

- **AsCommand** - Class-level attribute defining command name (`#[AsCommand(name: 'greet')]`)
- **CommandArgument** - Property-level attribute for positional arguments with defaults and interactive prompting
- **CommandOption** - Property-level attribute for named options with `isRequired` computed property using PHP 8.5 property hooks

### Input Parsing

- **Input** - Parsed command input DTO containing command name, arguments, and options
- **InputParser** - Parses `$argv` array into structured Input object (with helpers: ArgumentResolver, OptionResolver for parsing logic)
- **ValueCaster** - Type casts string input values to appropriate PHP types (int, float, bool, string)

### Output System

- **Output** - Readonly output implementation with formatted messages (`info`, `warning`, `error`, `success`, `question`)
- **OutputFormatter** - Processes output tags and applies color styles (with predefined styles: DangerStyle, InfoStyle, SuccessStyle, WarningStyle, NormalStyle)
- **OutputFormatterStyle** - Defines text appearance using ForegroundColor, BackgroundColor, and Style enums

### Service Provider

- **ConsoleServiceProvider** - Registers console services in DI container, sets up streams, initializes formatter styles

## PSR Standards Implemented

This chapter builds on existing PSR standards while focusing on console-specific functionality:

- **PSR-11**: Container integration - Commands are resolved through the PSR-11 compliant DI container, enabling dependency injection in command constructors
- **PSR-4**: Autoloading - All console classes follow PSR-4 namespace structure

## New Attributes

- **`#[AsCommand(name: 'command-name')]`** - Defines a console command's invocation name
- **`#[CommandArgument(name: 'arg', description: 'desc', value: default)]`** - Defines positional command arguments
- **`#[CommandOption(name: 'option', description: 'desc', value: default)]`** - Defines named command options

## Usage Examples

### Basic Example - Simple Command

```php
<?php

namespace App\Console\Commands;

use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Command;

#[AsCommand(name: 'hello')]
class HelloCommand extends Command
{
    public function run(): int
    {
        $this->output->success('Hello from Larafony!');
        return 0; // Success exit code
    }
}
```

Run the command:
```bash
php bin/console.php hello
```

### Intermediate Example - Command with Arguments and Options

```php
<?php

namespace App\Console\Commands;

use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Attributes\CommandArgument;
use Larafony\Framework\Console\Attributes\CommandOption;
use Larafony\Framework\Console\Command;

#[AsCommand(name: 'greet')]
class GreetCommand extends Command
{
    // Positional argument with default value
    #[CommandArgument(name: 'name', description: 'The name of the person to greet')]
    protected string $name = 'World';

    // Named option with default value
    #[CommandOption(name: 'greeting', description: 'The greeting to use')]
    protected string $greeting = 'Hello';

    // Boolean flag option
    #[CommandOption(name: 'shout', description: 'Shout the greeting')]
    protected bool $shout = false;

    public function run(): int
    {
        $message = "{$this->greeting}, {$this->name}!";

        if ($this->shout) {
            $message = strtoupper($message);
        }

        $this->output->success($message);
        return 0;
    }
}
```

Usage examples:
```bash
# Basic usage with default values
php bin/console.php greet
# Output: Hello, World!

# With custom argument
php bin/console.php greet John
# Output: Hello, John!

# With options
php bin/console.php greet John --greeting=Hi --shout
# Output: HI, JOHN!
```

### Advanced Example - Interactive Command with DI

```php
<?php

namespace App\Console\Commands;

use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Attributes\CommandArgument;
use Larafony\Framework\Console\Command;
use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Console\Contracts\OutputContract;
use Larafony\Framework\Container\Contracts\ContainerContract;

#[AsCommand(name: 'user:create')]
class UserCreateCommand extends Command
{
    // Argument without default - will prompt interactively if not provided
    #[CommandArgument(name: 'email', description: 'User email address')]
    protected string $email;

    #[CommandArgument(name: 'username', description: 'Username')]
    protected string $username;

    public function __construct(
        OutputContract $output,
        ContainerContract $container,
        private ConfigContract $config
    ) {
        parent::__construct($output, $container);
    }

    public function run(): int
    {
        // Access config from DI container
        $appName = $this->config->get('app.name');

        $this->output->info("Creating user in {$appName}...");
        $this->output->writeln("Email: {$this->email}");
        $this->output->writeln("Username: {$this->username}");

        // User creation logic here...

        $this->output->success('User created successfully!');
        return 0;
    }
}
```

If arguments are missing, the command prompts interactively:
```bash
php bin/console.php user:create
# Prompts: Enter value for argument email:
# User enters: john@example.com
# Prompts: Enter value for argument username:
# User enters: johndoe
```

## Comparison with Other Frameworks

| Feature | Larafony | Laravel | Symfony |
|---------|----------|---------|---------|
| Command Definition | `#[AsCommand]` attribute | Class extending `Command` | Class extending `Command` + `#[AsCommand]` (7.3+) |
| Arguments/Options | `#[CommandArgument]`, `#[CommandOption]` attributes on properties | `$signature` string DSL | `configure()` method or `#[Argument]`, `#[Option]` (7.3+) |
| Command Discovery | Automatic filesystem scan + attribute parsing | Manual registration or auto-discovery | Service tagging or auto-discovery |
| Interactive Prompts | Built-in via `CommandArgument` | `PromptsForMissingInput` trait | `QuestionHelper` |
| Output Formatting | Custom OutputFormatter with tags | Symfony Console styles | Native Symfony Console styles |
| Property Injection | Direct property setting via attributes | `argument()` and `option()` methods | `InputInterface` methods |
| PHP Features | PHP 8.5 `protected(set)`, property hooks | PHP 8.1+ features | PHP 8.2+ features |
| DI Integration | Constructor injection via PSR-11 | Service container injection | Service container injection |
| Dependencies | Zero (built from scratch) | Symfony Console package | Native component |

**Key Differences:**

- **Attribute-First Architecture**: Larafony uses attributes for both command metadata AND argument/option definitions directly on properties, while Laravel uses a signature DSL string and Symfony traditionally uses method calls (though 7.3+ adds attributes). This makes Larafony's approach more type-safe and IDE-friendly.

- **Property-Based Arguments**: Larafony injects arguments directly into command properties via reflection, eliminating the need to call `$this->argument('name')` or similar methods. Arguments are available as typed properties throughout the command.

- **PHP 8.5 Property Hooks**: Larafony leverages `protected(set)` for the `$output` property and computed properties in `CommandOption` (e.g., `$isRequired`), showcasing modern PHP capabilities not available in other frameworks.

- **From-Scratch Implementation**: Unlike Laravel (which wraps Symfony Console) and Symfony (which provides the console component), Larafony builds everything from scratch. This provides educational value and complete control but means some advanced features may not be present yet.

- **Automatic Interactive Mode**: When arguments lack default values, Larafony automatically prompts users interactively through the `CommandArgument::apply()` method. This is similar to Laravel's `PromptsForMissingInput` but built into the attribute system itself.

- **Simplified Output API**: Larafony's `Output` class provides a clean API (`info()`, `success()`, `error()`, `warning()`) with tag-based formatting, simpler than Symfony's extensive styling system but more powerful than raw echo statements.

## Enhanced Features

### Secret Input for Passwords

The `Output` class includes a `secret()` method for secure password input that hides characters as the user types using terminal capabilities (`stty -echo`). **Unix/Linux/macOS only** - Windows falls back to regular input.

```php
$password = $this->output->secret('Enter password: ');
// User input is hidden on Unix systems
```

### Default Values for Questions

The `question()` method accepts default values for improved interactive command UX:

```php
$host = $this->output->question('Enter host [127.0.0.1]: ', '127.0.0.1');
// User presses Enter to accept default
```

### Command Orchestration

Commands can call other commands via the `call()` method, enabling composition patterns:

```php
protected function call(string $command, array $arguments = []): int;

// Usage
$this->call('database:connect');
$this->call('migrate:fresh');
```

### Application Handle Defaults

The `Application::handle()` method automatically uses `$_SERVER['argv']` when arguments aren't provided, simplifying CLI usage.

## Real World Integration

This chapter's features are demonstrated in the demo application with a fully functional console command showcasing attribute-based arguments, options, and formatted output.

### Demo Application Changes

The demo application now includes:
- `bin/console.php` - Console entry point script
- `bootstrap/console_app.php` - Console application bootstrap with service providers
- `src/Console/Commands/GreetCommand.php` - Example command demonstrating all features

### File Structure
```
demo-app/
├── bin/
│   └── console.php                          # Console entry point
├── bootstrap/
│   └── console_app.php                      # Console bootstrap with service providers
└── src/
    └── Console/
        └── Commands/
            ├── .gitkeep
            └── GreetCommand.php             # Demo command with arguments and options
```

### Implementation Example

**File: `demo-app/bin/console.php`**

```php
<?php

declare(strict_types=1);

// Test PHP 8.5 first-class callable in const expression

/**
 * @var \Larafony\Framework\Console\Application $app
 */
$app = require_once __DIR__ . '/../bootstrap/console_app.php';
$app->handle();
```

This is the console entry point, similar to `artisan` in Laravel or `bin/console` in Symfony. It bootstraps the console application and delegates handling to the Application instance.

**File: `demo-app/bootstrap/console_app.php`**

```php
<?php

declare(strict_types=1);

use Larafony\Framework\Config\ServiceProviders\ConfigServiceProvider;
use Larafony\Framework\Console\ServiceProviders\ConsoleServiceProvider;
use Larafony\Framework\ErrorHandler\ServiceProviders\ErrorHandlerServiceProvider;
use Larafony\Framework\Http\ServiceProviders\HttpServiceProvider;

require_once __DIR__ . '/../vendor/autoload.php';

// Create console application instance with base path
$app = \Larafony\Framework\Console\Application::instance(base_path: dirname(__DIR__));

// Register service providers for console environment
$app->withServiceProviders([
    ErrorHandlerServiceProvider::class,  // Error handling for CLI
    HttpServiceProvider::class,          // HTTP utilities (may be needed for some commands)
    ConfigServiceProvider::class,        // Configuration system
    ConsoleServiceProvider::class,       // Console-specific services
]);

return $app;
```

**What's happening here:**
1. **Autoloading**: Composer autoloader is required for class loading
2. **Application Instantiation**: Console Application singleton is created with base path
3. **Service Provider Registration**: Multiple service providers are registered:
   - `ErrorHandlerServiceProvider`: Provides error handling for CLI errors
   - `HttpServiceProvider`: May be needed for commands that make HTTP requests
   - `ConfigServiceProvider`: Provides access to configuration and environment variables
   - `ConsoleServiceProvider`: **Critical** - Registers console-specific services like Output, OutputFormatter, CommandRegistry, and sets up input/output streams
4. **Return Application**: The configured application is returned to bin/console.php

**File: `demo-app/src/Console/Commands/GreetCommand.php`**

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Attributes\CommandArgument;
use Larafony\Framework\Console\Attributes\CommandOption;
use Larafony\Framework\Console\Command;

// Define command name using attribute - this makes the command available as "greet"
#[AsCommand(name: 'greet')]
class GreetCommand extends Command
{
    // Positional argument with default value
    // If user doesn't provide a name, "World" will be used
    // Attribute attaches metadata to the property for the resolver to process
    #[CommandArgument(name: 'name', description: 'The name of the person to greet')]
    protected string $name = 'World';

    // Named option with default value
    // Can be overridden with --greeting=CustomGreeting
    #[CommandOption(name: 'greeting', description: 'The greeting to use')]
    protected string $greeting = 'Hello';

    // Boolean flag option - defaults to false
    // Set to true with --shout flag (no value needed for booleans)
    #[CommandOption(name: 'shout', description: 'Shout the greeting')]
    protected bool $shout = false;

    public function run(): int
    {
        // At this point, all arguments and options have been injected into properties
        // by the CommandResolver through reflection

        // Build the greeting message using the injected values
        $message = "{$this->greeting}, {$this->name}!";

        // Apply shouting if the flag is set
        if ($this->shout) {
            $message = strtoupper($message);
        }

        // Use the Output instance (injected via protected(set) property hook)
        // to display formatted success message (green text)
        $this->output->success($message);

        // Return 0 for success (any non-zero value indicates error)
        return 0;
    }
}
```

**What's happening here:**
1. **Attribute-Based Definition**: `#[AsCommand(name: 'greet')]` makes this class discoverable as the "greet" command
2. **Property-Based Arguments**: Arguments and options are defined as typed properties with attributes
3. **Automatic Resolution**: The CommandResolver:
   - Reflects on the class to find CommandArgument and CommandOption attributes
   - Parses CLI input to extract argument and option values
   - Injects values directly into the properties before `run()` is called
4. **Type Safety**: All properties are typed (string, bool), and ValueCaster ensures correct types
5. **Formatted Output**: `$this->output->success()` uses the OutputFormatter to apply color tags
6. **Exit Code**: Return 0 for success, enabling shell scripts to check command status

### Running the Demo

```bash
cd demo-app

# Run with default values
php bin/console.php greet
# Output: Hello, World! (in green)

# With custom name (positional argument)
php bin/console.php greet Alice
# Output: Hello, Alice!

# With all options
php bin/console.php greet Bob --greeting=Howdy --shout
# Output: HOWDY, BOB!

# Mix and match
php bin/console.php greet "John Doe" --shout
# Output: HELLO, JOHN DOE!
```

**Expected output:**
```
Hello, World!
```
(Text will be green due to `success` style)

### Key Takeaways

- **Declarative Command Definition**: Attributes make command structure immediately visible without reading implementation
- **Zero Boilerplate**: No need to call `$this->argument('name')` or configure arguments in a method - just declare properties
- **Type Safety**: Property types ensure correct data types throughout the command
- **Automatic Discovery**: Commands are found automatically by scanning the Commands directory
- **Interactive Fallback**: If arguments don't have defaults, users are prompted interactively
- **Clean Separation**: Command logic in `run()` method focuses purely on business logic, not input parsing
- **Modern PHP**: Showcases PHP 8.5 features like `protected(set)` and property hooks in a practical context
- **Framework Integration**: Commands have full access to DI container, config, and all framework services

---

📚 **Learn More:** This implementation is explained in detail with step-by-step tutorials, tests, and best practices at [masterphp.eu](https://masterphp.eu)
