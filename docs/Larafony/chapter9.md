# Chapter 9: Console - Command Line Interface

This chapter implements a powerful console framework with automatic command discovery, attribute-based configuration, smart argument binding, and interactive prompting - all using PHP 8.4/8.5 cutting-edge features.

## Table of Contents

- [Overview](#overview)
- [Quick Start](#quick-start)
- [Core Concepts](#core-concepts)
- [Creating Commands](#creating-commands)
- [Input System](#input-system)
- [Output Formatting](#output-formatting)
- [Command Discovery & Caching](#command-discovery--caching)
- [Architecture Highlights](#architecture-highlights)
- [Complete Examples](#complete-examples)
- [Testing](#testing)

## Overview

Larafony's Console system provides:

- **Attribute-Based Commands** - Use `#[AsCommand]`, `#[CommandArgument]`, `#[CommandOption]`
- **Smart Attributes** - Attributes contain behavior, not just metadata
- **Auto-Discovery** - Scans and caches commands automatically
- **Interactive Prompting** - Asks for missing required arguments
- **Type Casting** - Automatic conversion to int, float, bool
- **ANSI Formatting** - Beautiful colored output with styles
- **PHP 8.4/8.5 Features** - Property hooks, pipe operator, asymmetric visibility
- **Zero Magic** - Simple, debuggable code

## Quick Start

### 1. Create a Command

```php
namespace App\Console\Commands;

use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Attributes\CommandArgument;
use Larafony\Framework\Console\Attributes\CommandOption;
use Larafony\Framework\Console\Command;

#[AsCommand(name: 'greet')]
class GreetCommand extends Command
{
    #[CommandArgument(name: 'name', description: 'The name to greet')]
    protected string $name = 'World';

    #[CommandOption(name: 'greeting', description: 'Custom greeting')]
    protected string $greeting = 'Hello';

    #[CommandOption(name: 'shout', description: 'Shout the greeting')]
    protected bool $shout = false;

    public function run(): int
    {
        $message = "{$this->greeting}, {$this->name}!";

        if ($this->shout) {
            $message = strtoupper($message);
        }

        $this->output->success($message);

        return 0; // Success
    }
}
```

### 2. Register Service Provider

```php
// bootstrap/console.php
use Larafony\Framework\Console\Application;
use Larafony\Framework\Console\ServiceProviders\ConsoleServiceProvider;
use Larafony\Framework\Http\ServiceProviders\HttpServiceProvider;

$app = Application::instance(__DIR__ . '/..');

$app->withServiceProviders([
    HttpServiceProvider::class,
    ConsoleServiceProvider::class,
]);

exit($app->handle());
```

### 3. Run Commands

```bash
# List all commands
php bin/console

# Run command with defaults
php bin/console greet
# Output: Hello, World!

# Pass arguments
php bin/console greet Alice
# Output: Hello, Alice!

# Pass options
php bin/console greet Bob --greeting=Hi
# Output: Hi, Bob!

# Use flags
php bin/console greet "Damian" --greeting=Yo --shout
# Output: YO, DAMIAN!

# Cache commands for performance
php bin/console cache:commands
# Output: Cached 5 command(s) to storage/cache/commands.php
```

## Core Concepts

### Commands

Commands are classes that extend `Command` and have the `#[AsCommand]` attribute.

**Lifecycle:**
1. User runs `php bin/console greet Alice --shout`
2. InputParser parses argv into Input object
3. Kernel loads commands (from cache or discovery)
4. CommandRegistry finds `GreetCommand` by name
5. Container instantiates command with OutputContract
6. CommandResolver binds arguments/options to properties
7. Command::run() executes
8. Exit code returned

### Attributes

Larafony uses **Smart Attributes** - they contain behavior, not just metadata:

```php
#[CommandArgument(name: 'seeder', description: 'Seeder class')]
class CommandArgument
{
    // Smart attributes have methods!
    public function hasDefaultValue(ReflectionProperty $property, Command $command): bool { ... }
    public function getDefaultValue(ReflectionProperty $property, Command $command): mixed { ... }
    public function apply(ReflectionProperty $property, Command $command): void { ... }
}
```

**Benefits:**
- Each attribute knows how to apply itself
- Resolver is just a coordinator: `$attribute->apply($property, $command)`
- Strategy Pattern built-in
- Easy to extend with new attribute types

### Input & Output

**Input** - Represents parsed command line arguments:
```php
$input->command;        // Command name
$input->arguments;      // Positional arguments [0 => 'Alice', 1 => 'Bob']
$input->options;        // Named options ['greeting' => 'Hi', 'shout' => true]
```

**Output** - Formatted console output:
```php
$this->output->info('Info message');       // Cyan
$this->output->success('Success!');        // Green
$this->output->warning('Warning!');        // Yellow
$this->output->error('Error!');            // Red
$this->output->writeln('Plain text');
$this->output->question('Enter name:');    // Interactive input
```


### Required Options with Convention

Use `?` prefix for optional options:

```php
#[AsCommand(name: 'backup:create')]
class BackupCommand extends Command
{
    #[CommandOption(name: '?destination', description: 'Backup destination')]
    protected string $destination;

    public function run(): int
    {
        $this->output->info("Backing up to {$this->destination}");
        return 0;
    }
}
```

## Input System

### InputParser

Parses `$argv` into structured Input object:

```php
$parser = new InputParser();
$input = $parser->parse(['bin/console', 'greet', 'Alice', '--shout']);

echo $input->command;           // 'greet'
echo $input->arguments[0];      // 'Alice'
echo $input->options['shout'];  // 'shout' (flag present)
```

**Simple but powerful:**
- Arguments: anything NOT starting with `--`
- Options: anything starting with `--`
- Automatic `--` stripping for options

### ValueCaster

Automatically casts string values to appropriate types:

```php
ValueCaster::cast('123');      // int(123)
ValueCaster::cast('3.14');     // float(3.14)
ValueCaster::cast('true');     // bool(true)
ValueCaster::cast('yes');      // bool(true)
ValueCaster::cast('false');    // bool(false)
ValueCaster::cast('hello');    // string('hello')
```

**Boolean mappings:**
- `true`: '1', 'on', 'true', 'yes'
- `false`: '0', 'off', 'false', 'no'

**Numeric detection:**
- `ctype_digit()` → int
- `is_numeric()` → float
- Otherwise → string

## Output Formatting

### Output Class

```php
$output->write('Text');                // No newline
$output->writeln('Text with newline'); // With newline
$output->info('Info');                 // Cyan text
$output->success('Success');           // Green text
$output->warning('Warning');           // Yellow text
$output->error('Error');               // Red text
$output->question('Prompt:');          // Interactive input
```

### Styles

Built-in styles registered in `ConsoleServiceProvider::boot()`:

```php
// DangerStyle - RED (31)
$this->output->error('Error message');

// InfoStyle - CYAN (36)
$this->output->info('Information');

// SuccessStyle - GREEN (32)
$this->output->success('Operation successful');

// WarningStyle - YELLOW (33)
$this->output->warning('Warning message');
```

### Custom Styles

Create your own style:

```php
use Larafony\Framework\Console\Enums\ForegroundColor;
use Larafony\Framework\Console\Enums\BackgroundColor;
use Larafony\Framework\Console\Enums\Style;
use Larafony\Framework\Console\Formatters\OutputFormatterStyle;

$customStyle = new OutputFormatterStyle(
    backgroundColor: BackgroundColor::BLUE,
    foregroundColor: ForegroundColor::WHITE,
    style: Style::BOLD
);

$formatter = $container->get(OutputFormatter::class);
$formatter->withStyle('highlight', $customStyle);
```

### OutputFormatter

Formats text with ANSI codes:

```php
$formatter->format('<info>Cyan text</info>');
// Returns: "\033[36mCyan text\033[0m"

$formatter->format('<success>Green</success> and <danger>Red</danger>');
// Multiple styles in one string
```

## Command Discovery & Caching

### Auto-Discovery

Commands are automatically discovered from configured paths:

```php
// Kernel scans these paths
$commandPaths = [
    '/path/to/app/Console/Commands' => 'App\\Console\\Commands',
];

// Finds all PHP files
// Filters for classes with #[AsCommand] attribute
// Registers in CommandRegistry
```

### Caching

Cache commands for production performance:

```bash
# Cache all discovered commands
php bin/console cache:commands

# Creates: storage/cache/commands.php
# Contains: array mapping 'command-name' => CommandClass::class
```

**How it works:**

1. `Kernel::handle()` checks if cache exists
2. If yes: load from cache (fast)
3. If no: run discovery (slower, dev mode)
4. Discovery scans directories for `#[AsCommand]` attributes

**Cache file example:**
```php
<?php

return [
    'greet' => App\Console\Commands\GreetCommand::class,
    'user:create' => App\Console\Commands\CreateUserCommand::class,
    'database:migrate' => App\Console\Commands\MigrateCommand::class,
];
```

### CommandDiscovery

Scans directories and finds commands:

```php
$discovery = new CommandDiscovery();
$discovery->discover('/app/Console/Commands', 'App\\Console\\Commands');

// Result: $discovery->commands contains command map
```

**Uses modern PHP features:**
- `Directory` helper for recursive file scanning
- `FileToClassNameConverter` for path → FQCN conversion
- Pipe operator for filtering
- Reflection for attribute detection

## Architecture Highlights

### PHP 8.4 Property Hooks

**Input with asymmetric visibility:**
```php
class Input
{
    public private(set) array $options {
        get => $this->options;
        set {
            $this->options = [];
            foreach ($value as $option) {
                $option = str_replace('--', '', $option);
                $this->options[$option] = $option;
            }
        }
    }
}
```

**Benefits:**
- Public read, private write
- Custom setter logic in property hook
- Eliminates traditional getter/setter methods

**Command with protected(set):**
```php
abstract class Command
{
    public protected(set) OutputContract $output;

    public function __construct(OutputContract $output)
    {
        $this->output = $output;
    }
}
```

**CommandOption with computed property:**
```php
class CommandOption
{
    public bool $isRequired {
        get => str_starts_with($this->name, '?');
    }
}
```

Accessing `$option->isRequired` executes the hook - no separate method needed!

### PHP 8.5 Pipe Operator

**Smart attribute with pipe:**
```php
public function apply(ReflectionProperty $property, Command $command): void
{
    $value = $command->output->question('Enter value for argument ' . $this->name . ':')
            |> ValueCaster::cast(...);
    $property->setValue($command, $value);
}
```

Reads top-to-bottom:
1. Ask question
2. Pipe answer to ValueCaster
3. Cast to appropriate type
4. Set property value

### Separation of Concerns

Clean architecture with focused classes:

```
Application (DI Container + singleton)
    └─> Kernel (orchestration)
        ├─> InputParser (argv → Input)
        ├─> CommandCache (load/save cache)
        ├─> CommandRegistry (command map)
        │   └─> CommandDiscovery (scan & find)
        ├─> CommandResolver (bind args/options)
        │   ├─> ArgumentResolver
        │   └─> OptionResolver
        └─> Command::run() (execute)
```

Each class has ONE job:
- `InputParser` - Parse argv
- `CommandCache` - Load/save cache
- `CommandRegistry` - Store command map
- `CommandDiscovery` - Find commands
- `CommandResolver` - Bind input to properties
- `ArgumentResolver` - Handle arguments
- `OptionResolver` - Handle options
- `Command` - Execute business logic


## Testing

### Running Tests

```bash
# All Console tests
php8.5 vendor/bin/phpunit tests/Larafony/Console --testdox

# Specific test class
php8.5 vendor/bin/phpunit tests/Larafony/Console/Input/InputParserTest.php

# With coverage
composer test-coverage
```

### Test Structure

```
tests/Larafony/Console/
├── Input/
│   ├── InputParserTest.php
│   ├── InputTest.php
│   └── ValueCasterTest.php
├── Resolvers/
│   ├── ArgumentResolverTest.php
│   └── OptionResolverTest.php
├── Attributes/
│   ├── CommandArgumentTest.php
│   └── CommandOptionTest.php
├── Formatters/
│   ├── OutputFormatterTest.php
│   └── Styles/
│       └── StylesTest.php
├── CommandRegistryTest.php
├── CommandDiscoveryTest.php
├── CommandCacheTest.php
├── KernelTest.php
└── ApplicationTest.php
```

## Key Differences from Symfony Console

| Feature | Symfony Console | **Larafony Console** |
|---------|----------------|----------------------|
| Configuration | Class methods | **✓ Attributes** |
| Argument Binding | Manual `$input->getArgument()` | **✓ Automatic binding** |
| Interactive | `QuestionHelper` | **✓ Smart attributes** |
| Type Casting | Manual | **✓ Automatic (ValueCaster)** |
| Discovery | Manual registration | **✓ Auto-discovery + cache** |
| Complexity | High (many classes) | **✓ Simple (focused classes)** |
| PHP 8.4/8.5 | ✗ | **✓ (property hooks, pipes)** |
| Smart Attributes | ✗ | **✓ (attributes with behavior)** |
| Code Lines | ~500+ for basic setup | **✓ ~30 lines** |

## Related Documentation

- [Framework README](../../README.md)
- [Chapter 4: Dependency Injection](./chapter4.md)
- [Chapter 8: Configuration & Environment](./chapter8.md)

## References

- [PHP 8.4 Property Hooks](https://wiki.php.net/rfc/property-hooks)
- [PHP 8.4 Asymmetric Visibility](https://wiki.php.net/rfc/asymmetric-visibility-v2)
- [PHP 8.5 Pipe Operator](https://wiki.php.net/rfc/pipe-operator-v2)
- [Symfony Console Component](https://symfony.com/doc/current/components/console.html)

## What's Next?

**Chapter 10** will introduce Database abstraction with Query Builder, migrations, and ORM - all built from scratch with modern PHP features!
