# Chapter 12: Database Migrations System

> Part of the Larafony Framework - A modern PHP 8.5 framework built from scratch
>
> 📚 Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)

## Overview

This chapter introduces a comprehensive database migration system that enables version-controlled database schema management. Migrations allow developers to define database structure changes using PHP code, track migration history, and safely apply or rollback changes across different environments.

The migration system is built with a clean architecture that separates concerns into distinct components: resolution (finding migrations), execution (running migrations), and repository (tracking migration history). Each component follows SOLID principles and is fully testable. The system uses PHP 8.5's latest features including asymmetric visibility (`public protected(set)`) and the pipe operator for clean data transformations.

The implementation includes four console commands for complete migration lifecycle management: creating new migrations, running pending migrations, rolling back changes, and refreshing the entire database. All commands integrate seamlessly with the existing console framework established in previous chapters.

## Key Components

### Migration Core

- **MigrationContract** - Interface defining `up()` and `down()` methods for applying and reverting changes
- **Migration** - Abstract base class with name tracking using PHP 8.5's asymmetric visibility and the `clone()` function for immutability
- **MigrationRepository** - Abstract repository managing the `migrations` table, tracking executed migrations by batch number (uses helper methods: `log()`, `delete()`, `getRan()`, `getMigrationsByBatch()`, `getLastBatchNumber()`)
- **MigrationResolver** - Resolves migration files from the filesystem using pipe operator for clean transformations, validates filename format `YYYY_MM_DD_HHMMSS_name.php`
- **MigrationExecutor** - Coordinates migration execution by delegating to resolver and repository

### Console Commands

- **MakeMigration** - Generates timestamped migration files from stub template
- **Migrate** - Executes pending migrations with batch tracking and optional `--step` support
- **MigrateRollback** - Reverts migrations by batch with configurable `--step` count
- **MigrateFresh** - Drops all tables and re-runs all migrations for clean database state

## PSR Standards Implemented

While this chapter focuses on framework-specific migration functionality rather than implementing new PSR standards, the architecture maintains compliance with previously established PSRs:

- **PSR-11**: Container integration for dependency injection in commands and services
- **PSR-4**: Autoloading follows strict namespace conventions for migration classes

The migration system integrates with the existing database layer (PSR-compliant) and console framework to provide a cohesive developer experience.

## New Attributes

No new attributes were introduced in this chapter. The migration commands use existing attributes from the console framework:

- `#[AsCommand]` - Registers migration commands (e.g., `migrate`, `migrate:rollback`, `migrate:fresh`, `make:migration`)
- `#[CommandOption]` - Defines command options like `--database`, `--force`, `--step`
- `#[CommandArgument]` - Defines arguments like migration name in `make:migration`

## Usage Examples

### Basic Example: Creating and Running Migrations

```php
<?php
// 1. Create a new migration
// Command: php larafony make:migration create_users_table
// Generates: database/migrations/2025_10_23_143022_create_users_table.php

namespace App\Database\Migrations;

use Larafony\Framework\Database\Base\Migrations\Migration;
use Larafony\Framework\Database\Schema;
use Larafony\Framework\Database\Base\Schema\TableDefinition;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (TableDefinition $table): void {
            $table->id();
            $table->string('name', 255);
            $table->string('email', 255);
            $table->timestamps();
        }) |> Schema::execute(...);
    }

    public function down(): void
    {
        Schema::drop('users') |> Schema::execute(...);
    }
};

// 2. Run the migration
// Command: php larafony migrate
// Output: Migrated: 2025_10_23_143022_create_users_table
```

### Advanced Example: Complex Table with Pipe Operator

```php
<?php
// Create a posts table with relationships and indexes
// Command: php larafony make:migration create_posts_table

namespace App\Database\Migrations;

use Larafony\Framework\Database\Base\Migrations\Migration;
use Larafony\Framework\Database\Schema;
use Larafony\Framework\Database\Base\Schema\TableDefinition;

return new class extends Migration
{
    public function up(): void
    {
        // Create table using pipe operator for clean, functional style
        Schema::create('posts', function (TableDefinition $table): void {
            $table->id();
            $table->integer('user_id');
            $table->string('title', 255);
            $table->text('content');
            $table->string('status', 50);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        }) |> Schema::execute(...);
    }

    public function down(): void
    {
        // Drop table with pipe operator - reads naturally left to right
        Schema::drop('posts') |> Schema::execute(...);
    }
};

// Batch Management Example:
// - Batch 1: 2025_10_23_120000_create_users_table
// - Batch 2: 2025_10_23_130000_create_posts_table (current migration)
// - Command: php larafony migrate:rollback --step=2
// - Rolls back both batches in reverse chronological order
```

### Fresh Migration Example

```php
<?php
// Complete database refresh for development/testing

// Command: php larafony migrate:fresh
// Output:
// Dropped: users
// Dropped: posts
// Dropped: migrations
// Migrated: 2025_10_23_120000_create_users_table
// Migrated: 2025_10_23_130000_create_posts_table
// Migrated: 2025_10_23_140000_add_published_column

// Use case: Clean slate for integration tests or development reset
```

## Implementation Details

### Key Architectural Decisions

**1. Batch Tracking System**

The migration repository tracks migrations in batches rather than individual timestamps. This allows rolling back multiple migrations as a logical unit:

```php
// MigrationRepository::log() stores batch number
$this->queryBuilder()->table('migrations')->insert([
    'migration' => $migration,
    'batch' => $this->getNextBatchNumber(), // Incremental batch tracking
]);

// MigrateRollback can rollback multiple batches
for ($i = 0; $i < $steps; $i++) {
    $batchNumber = $lastBatch - $i;
    $batchMigrations = $this->repository->getMigrationsByBatch($batchNumber);
    $migrations = array_merge($migrations, $batchMigrations);
}
```

**2. Filename-Based Ordering**

Migration files use timestamp prefixes (`YYYY_MM_DD_HHMMSS_`) ensuring chronological execution:

```php
// MigrationResolver validates format with regex
private function isMigrationFile(\SplFileInfo $file): bool
{
    $name = $file->getFilename();
    return (bool) preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_\w+\.php$/', $name);
}
```

**3. Anonymous Classes for Migrations**

Migrations return anonymous classes extending `Migration`, avoiding namespace conflicts and simplifying file structure:

```php
// Stub template uses anonymous class
return new class extends Migration
{
    public function up(): void { /* ... */ }
    public function down(): void { /* ... */ }
};
```

**4. PHP 8.5 Features**

The migration system showcases modern PHP:

- **Asymmetric visibility** (PHP 8.4): `public protected(set) string $name` in `Migration` class - property can be read publicly but only set within the class
- **Pipe operator** (PHP 8.5): `Schema::drop('users') |> Schema::execute(...)` - creates elegant, left-to-right data flow
- **First-class callable syntax** (PHP 8.1): `Schema::execute(...)` creates a callable reference, perfect for pipe operator
- **Pipe operator for transformations**: Clean data transformation chain in `MigrationResolver::getMigrationFiles()`:
  ```php
  $files = new Directory($this->migrationPath)->files
      |> (fn ($files) => array_filter($files, $this->isMigrationFile(...)))
      |> (static fn ($files) => array_map(
          static fn (\SplFileInfo $file) => pathinfo($file->getFilename(), PATHINFO_FILENAME),
          $files
      ));
  ```
- **`clone()` function** (PHP 8.5): Immutable name assignment with `withName()` method using `clone($this, ['name' => $name])`

The pipe operator (`|>`) combined with first-class callable syntax is particularly elegant in migrations:
```php
// Old style (nested, reads right-to-left)
Schema::execute(Schema::create('users', $callback));

// New style (pipeline, reads left-to-right)
Schema::create('users', $callback) |> Schema::execute(...);
```

> **Note:** If the Partial Function Application RFC gets accepted and implemented (hopefully in PHP 8.6/9.0), this could be simplified even further. Currently we use:
> ```php
> // Current approach with FCC (PHP 8.1) + pipe operator (PHP 8.5)
> $files = new Directory($this->migrationPath)->files
>     |> (fn ($files) => array_filter($files, $this->isMigrationFile(...)))
>     |> (static fn ($files) => array_map(
>         static fn (\SplFileInfo $file) => pathinfo($file->getFilename(), PATHINFO_FILENAME),
>         $files
>     ));
> ```
>
> With PFA (using `?` placeholder for single argument):
> ```php
> // Future approach with PFA - cleaner, no arrow functions needed
> $files = new Directory($this->migrationPath)->files
>     |> array_filter(?, $this->isMigrationFile(...))
>     |> array_map(static fn($file) => pathinfo($file->getFilename(), PATHINFO_FILENAME), ?);
> ```
>
> The `?` placeholder would indicate where the piped value should be inserted, eliminating wrapper arrow functions and making the pipeline even more readable. See [RFC: Partial Function Application](https://wiki.php.net/rfc/partial_function_application) for details.

**5. Separation of Concerns**

Each component has a single responsibility:
- `MigrationResolver` - File system operations and validation
- `MigrationExecutor` - Orchestrates execution logic
- `MigrationRepository` - Database state management
- Commands - User interaction and CLI interface

## Comparison with Other Frameworks

| Feature | Larafony | Laravel | Symfony (Doctrine) |
|---------|----------|---------|-------------------|
| **Migration Format** | Anonymous PHP classes | Named PHP classes | PHP classes with annotations |
| **Batch Tracking** | Yes, with batch numbers | Yes, with batch numbers | No, uses sequential versioning |
| **Rollback Support** | Yes, with `--step` option | Yes, with `--step` option | Yes, via `doctrine:migrations:migrate prev` |
| **Schema Builder** | Framework's Schema facade | Schema builder (Blueprint) | DQL/Raw SQL or schema manager |
| **File Naming** | `YYYY_MM_DD_HHMMSS_name.php` | `YYYY_MM_DD_HHMMSS_name.php` | `VersionYYYYMMDDHHMMSS.php` |
| **Migration Table** | `migrations` table | `migrations` table | `migration_versions` table |
| **Fresh Command** | `migrate:fresh` drops all tables | `migrate:fresh` drops all tables | No equivalent (use `schema:drop` + `migrate`) |
| **Generation** | Stub-based with timestamp | Artisan command with timestamp | Doctrine diff between entities and schema |
| **PSR Compliance** | Uses PSR-11 container | Uses PSR-11 container | Full Doctrine ORM integration |
| **Approach** | Manual schema definitions | Schema builder fluent API | Entity annotations + auto-generation |

**Key Differences:**

- **Larafony** and Laravel use anonymous classes for migrations, reducing boilerplate and avoiding namespace management. Symfony uses named classes requiring explicit class names and imports.

- **Symfony's Doctrine Migrations** automatically generates migrations by comparing entity mappings with the database schema. Both Larafony and Laravel require manual schema definitions, giving developers more control but requiring more code.

- **Batch tracking** in Larafony and Laravel allows rolling back multiple migrations as a unit (e.g., `--step=3` rolls back last 3 batches). Symfony uses sequential versioning, requiring explicit version targeting.

- **Larafony's `migrate:fresh`** and **Laravel's equivalent** drop all tables including non-migration tables. Symfony requires separate commands for dropping schema and running migrations.

- **File naming conventions** differ slightly but all use timestamps for ordering. Larafony's format (`YYYY_MM_DD_HHMMSS`) provides clear visual separation of date components.

- **PHP 8.5 features** in Larafony (asymmetric visibility, pipe operator, `clone()` function) showcase modern PHP capabilities not present in Laravel or Symfony's older codebase.

## Real World Integration

This chapter's features are demonstrated in the demo application configuration with practical migration path setup.

### Demo Application Changes

The demo application's database configuration was updated to include migration path settings, enabling the framework to locate and execute migration files in the application directory structure.

### File Structure
```
demo-app/
└── config/
    └── database.php - Added migrations path configuration
```

### Implementation Example

**File: `demo-app/config/database.php`**

```php
<?php

declare(strict_types=1);

use Larafony\Framework\Config\Environment\EnvReader;

return [
    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    */
    'default' => EnvReader::read('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Migrations Configuration
    |--------------------------------------------------------------------------
    |
    | This section defines where migration files are stored in your application.
    | The MigrationResolver uses this path to discover and load migration files.
    |
    | The path is relative to the application root directory.
    */
    'migrations' => [
        'path' => 'database/migrations',  // Migration files location
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    */
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => EnvReader::read('DB_HOST', '127.0.0.1'),
            'port' => (int) EnvReader::read('DB_PORT', '3306'),
            'database' => EnvReader::read('DB_DATABASE', 'larafony'),
            'username' => EnvReader::read('DB_USERNAME', 'root'),
            'password' => EnvReader::read('DB_PASSWORD', ''),
            'charset' => EnvReader::read('DB_CHARSET', 'utf8mb4'),
            'collation' => EnvReader::read('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => EnvReader::read('DB_PREFIX', ''),
            'strict' => (bool) EnvReader::read('DB_STRICT_MODE', 'true'),
            'engine' => EnvReader::read('DB_ENGINE', 'InnoDB'),
        ],
    ],
];
```

**What's happening here:**

1. **Configuration Array Structure**: The database configuration returns an array with three main sections: `default` connection name, `migrations` settings, and `connections` definitions.

2. **Migrations Path Configuration**: The `migrations.path` key specifies where the framework should look for migration files. This integrates with the `MigrationResolver` class which reads this configuration:
   ```php
   // In MakeMigration command (src/Larafony/Console/Commands/MakeMigration.php:31)
   $path = $this->container->get(ConfigContract::class)
       ->get('database.migrations.path', 'database/migrations/');
   ```

3. **Environment-Based Configuration**: Uses `EnvReader` for database credentials, allowing different settings per environment (development, staging, production) without code changes.

4. **Default Values**: Every configuration option includes a sensible default (e.g., `'127.0.0.1'` for host), ensuring the framework works out-of-the-box for local development.

5. **Type Safety**: Uses explicit type casting (e.g., `(int)` for port, `(bool)` for strict mode) ensuring configuration values match expected types throughout the framework.

### Running the Demo

To use migrations in the demo application:

```bash
# 1. Navigate to demo application
cd framework/demo-app

# 2. Create a new migration
php ../bin/larafony make:migration create_users_table

# 3. Edit the generated migration in database/migrations/
# (File will be: database/migrations/YYYY_MM_DD_HHMMSS_create_users_table.php)

# 4. Run the migration
php ../bin/larafony migrate

# 5. Check database - you'll see two tables:
# - migrations (framework-managed tracking table)
# - users (your application table)
```

**Expected output:**

```
# After make:migration
Utworzono 2025_10_23_143022_create_users_table.php

# After migrate
Migrated: 2025_10_23_143022_create_users_table

# After migrate (if run again with no pending migrations)
Nothing to migrate
```

### Key Takeaways

- **Configuration-Driven**: The migration path is configurable, not hardcoded, allowing applications to organize migrations as needed.

- **Environment Separation**: Database credentials use environment variables, following 12-factor app principles for deployment flexibility.

- **Convention with Flexibility**: Default values (`database/migrations`) provide convention, but applications can override for custom structures.

- **Framework Integration**: The configuration seamlessly connects user code (demo-app) with framework services (`MigrationResolver`, `MakeMigration` command).

- **Type Safety Throughout**: Configuration enforces types, preventing runtime errors from invalid configuration values.

- **Real-World Ready**: This configuration pattern scales from local development to production deployments with environment-specific `.env` files.

---

📚 **Learn More:** This implementation is explained in detail with step-by-step tutorials, tests, and best practices at [masterphp.eu](https://masterphp.eu)
