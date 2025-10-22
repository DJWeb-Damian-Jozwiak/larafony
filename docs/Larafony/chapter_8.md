# Chapter 8: Configuration and Environment

> Part of the Larafony Framework - A modern PHP 8.5 framework built from scratch
>
> 📚 Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)

## Overview

Chapter 8 implements configuration management with .env file parsing and type-safe configuration classes. This component separates environment-specific values (.env) from application configuration (config/*.php), following twelve-factor app methodology.

The implementation provides **EnvironmentLoader** for parsing .env files with variable expansion and type coercion, **ConfigRepository** for managing config arrays with dot notation, and **ConfigBase** abstract class for creating type-safe configuration objects.

## Key Components

- **EnvironmentLoader** - Parses .env files into $_ENV (helpers: EnvParser, VariableExpander, TypeCoercer)
- **ConfigRepository** - Array-based config storage with dot notation access
- **ConfigBase** - Abstract base for type-safe config classes
- **Environment DTOs** - EnvironmentVariable, ParsedLine, LineType, ParserResult for parsing

## PSR Standards Implemented

- **Type Safety**: Strict types, readonly properties, backed enums
- **Twelve-Factor App**: Environment-based configuration

## Usage Examples

```php
<?php

// Load .env
$loader = new EnvironmentLoader();
$loader->load(__DIR__ . '/.env');

// Access environment
$dbHost = env('DB_HOST', 'localhost');
$debug = env('APP_DEBUG', false); // Type coercion

// Type-safe config
class AppConfig extends ConfigBase {
    public function __construct(
        public readonly string $name,
        public readonly bool $debug,
    ) {}
}

$config = new AppConfig(
    name: env('APP_NAME'),
    debug: env('APP_DEBUG', false)
);
```

---

📚 **Learn More:** This implementation is explained in detail with step-by-step tutorials, tests, and best practices at [masterphp.eu](https://masterphp.eu)
