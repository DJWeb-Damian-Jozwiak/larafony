# Chapter 1: Base Framework Configuration

## Overview

Chapter 1 establishes the foundation of the Larafony framework by setting up the basic project structure, development tools, and quality standards. This chapter focuses on creating a solid development environment with comprehensive testing and code quality tools.

## What You'll Learn

- Setting up a modern PHP 8.5 project structure
- Configuring Composer with PSR-4 autoloading
- Implementing comprehensive testing with PHPUnit
- Ensuring code quality with PHPStan and PHP Insights
- Establishing development standards and workflows

## Project Structure

```
framework/
├── src/
│   └── Larafony/          # Framework source code (PSR-4)
├── tests/
│   └── Larafony/          # Test suite (PSR-4)
├── vendor/                # Composer dependencies
├── docs/                  # Documentation
├── composer.json          # Project configuration
└── README.md             # Project overview
```

## Composer Configuration

The `composer.json` file defines the project metadata, dependencies, and development tools:

### Production Dependencies

```json
{
    "require": {
        "php": ">=8.5"
    }
}
```

Currently, the framework has **zero external dependencies** in production, relying only on PHP 8.5. This aligns with Larafony's philosophy of minimal dependencies and building features from scratch.

### Development Dependencies

```json
{
    "require-dev": {
        "phpunit/phpunit": "^12.4.0",
        "phpstan/phpstan": "^2.1.30",
        "nunomaduro/phpinsights": "dev-master"
    }
}
```

**PHPUnit** - Testing framework for unit and integration tests
**PHPStan** - Static analysis tool for finding bugs without running code
**PHP Insights** - Code quality analyzer checking architecture, complexity, and style

### PSR-4 Autoloading

```json
{
    "autoload": {
        "psr-4": {
            "Larafony\\Framework\\": "src/Larafony/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Larafony\\Framework\\Tests\\": "tests/Larafony/"
        }
    }
}
```

This configuration enables automatic class loading following PSR-4 standards, mapping namespaces to directory structures.

## Development Scripts

Composer scripts provide convenient shortcuts for common development tasks:

### Testing

```bash
# Run all tests without coverage
composer test

# Run tests with text coverage report
composer test-coverage

# Generate HTML coverage report (outputs to coverage/ directory)
composer test-coverage-html
```

### Code Analysis

```bash
# Run PHPStan static analysis
composer analyse

# Run PHP Insights code quality check
composer insights

# Auto-fix PHP Insights issues
composer insights:fix

# Enforce quality thresholds (95% minimum on all metrics)
composer insights:quality
```

### Complete Quality Check

```bash
# Run all quality checks: static analysis, tests, and quality metrics
composer quality
```

This command runs:
1. `composer analyse` - PHPStan static analysis
2. `composer test` - Full PHPUnit test suite
3. `composer insights:quality` - Quality metrics validation

## PHP 8.5 Requirements

All development scripts explicitly use the `php8.5` binary to ensure compatibility:

```json
{
    "scripts": {
        "test": "php8.5 vendor/bin/phpunit --no-coverage"
    }
}
```

### Installing PHP 8.5

On Ubuntu/Debian:
```bash
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install php8.5-cli php8.5-fpm php8.5-dev
```

Verify installation:
```bash
php8.5 --version
```

### Installing PCOV for Code Coverage

**Important:** As of now, neither PCOV nor Xdebug are available as pre-built packages for PHP 8.5. You need to install PCOV from source:

```bash
# Install required build tools
sudo apt install build-essential autoconf

# Download and install PCOV
git clone https://github.com/krakjoe/pcov.git
cd pcov
phpize8.5
./configure --enable-pcov
make
sudo make install

# Enable PCOV extension
echo "extension=pcov.so" | sudo tee /etc/php/8.5/mods-available/pcov.ini
sudo phpenmod -v 8.5 pcov

# Verify installation
php8.5 -m | grep pcov
```

**Note:** Once PCOV becomes available in the official PHP 8.5 repositories, you'll be able to install it with:
```bash
sudo apt install php8.5-pcov
```

## Quality Standards

### PHP Insights Metrics

The framework enforces strict quality thresholds:

- **Code Quality**: Minimum 95%
- **Complexity**: Minimum 95%
- **Architecture**: Minimum 95%
- **Style**: Minimum 95%

These high standards ensure production-ready code from the start.

### PHPStan Configuration

PHPStan performs static analysis to catch bugs early. Configuration will be added in future chapters as the codebase grows.

### PHPUnit Configuration

PHPUnit runs the test suite with configurable coverage reporting. As the framework develops, tests will be added for each component.

## Development Workflow

### 1. Initial Setup

```bash
cd framework
composer install
```

### 2. Write Tests First (TDD)

Following Test-Driven Development principles:
1. Write a failing test in `tests/Larafony/`
2. Run `composer test` to verify it fails
3. Implement the feature in `src/Larafony/`
4. Run `composer test` to verify it passes
5. Refactor if needed

### 3. Run Quality Checks

Before committing:
```bash
composer quality
```

This ensures:
- No static analysis errors
- All tests pass
- Quality metrics meet 95% thresholds

### 4. Commit Changes

Use meaningful commit messages describing the feature or fix.

## Key Principles Established

### 1. Minimal Dependencies

The framework starts with **zero production dependencies**, adding only what's absolutely necessary. This keeps the codebase lean, maintainable, and secure.

### 2. PSR-First Architecture

All future components will follow PSR standards:
- PSR-4 for autoloading
- PSR-7 for HTTP messages
- PSR-11 for dependency injection
- PSR-15 for middleware
- And more...

### 3. Test-Driven Development

Every feature must include tests. The test suite ensures reliability and prevents regressions.

### 4. High Quality Standards

95% minimum on all quality metrics is non-negotiable. This produces production-grade code, not tutorial examples.

### 5. Modern PHP

Full use of PHP 8.5 features:
- Attributes for configuration
- Type declarations for safety
- Enums for constants
- New syntax and features

## What's Next

**Chapter 2** will implement simple error handling, providing a foundation for managing exceptions and errors gracefully throughout the framework.

Future chapters will build on this foundation:
- HTTP layer with PSR-7/PSR-17
- Dependency injection with PSR-11
- Routing and middleware with PSR-15
- Database abstraction and ORM
- View layer with Blade and Twig support
- And much more...

## Common Issues

### PHP Version Mismatch

If you encounter errors about PHP version:
```bash
# Verify PHP version
php8.5 --version

# Update Composer to use PHP 8.5
composer config platform.php 8.5
```

### Autoload Issues

After adding new classes, regenerate autoload files:
```bash
composer dump-autoload
```

### Permission Errors

Ensure proper permissions:
```bash
chmod -R 755 src/ tests/
```

## Web Server Configuration

For production deployment, you'll need to configure a web server:

- [Apache Configuration Guide](../apache.md) - Complete Apache setup with virtual hosts, SSL, and optimization
- [Nginx Configuration Guide](../nginx.md) - Complete Nginx setup with PHP-FPM, SSL, and performance tuning

## Summary

Chapter 1 establishes:
- ✅ Clean project structure with PSR-4 autoloading
- ✅ Comprehensive testing setup with PHPUnit
- ✅ Code quality tools (PHPStan, PHP Insights)
- ✅ High quality standards (95% minimum)
- ✅ Modern PHP 8.5 requirements
- ✅ Developer-friendly Composer scripts
- ✅ Foundation for future chapters

The framework is now ready for feature development, with all the tools needed to maintain high code quality and reliability.
