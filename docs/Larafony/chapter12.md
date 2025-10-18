# Chapter 12: MySQL Migrations

## Overview

In this chapter, we implemented a complete **MySQL Migrations** system that provides version control for database schema. The implementation allows you to track, execute, and rollback database changes across different environments, following Laravel's migration patterns while maintaining Larafony's architectural principles.

## Core Philosophy: Database Version Control

Migrations treat database schema as **versioned code**, allowing you to:

```php
// Create a new migration
php bin/console.php make:migration create_users_table

// Run all pending migrations
php bin/console.php migrate

// Rollback the last batch
php bin/console.php migrate:rollback

// Rollback last 3 batches
php bin/console.php migrate:rollback --step=3

// Drop all tables and re-run all migrations
php bin/console.php migrate:fresh
```

### Why Migrations Matter

**Without migrations:**
- Manual SQL scripts scattered across environments
- No history of schema changes
- Deployment nightmares (did we run this script?)
- Difficult to rollback changes
- Team coordination issues

**With migrations:**
- Version-controlled schema changes
- Reproducible database setup
- Easy deployment across environments
- Built-in rollback support
- Clear migration history

## Architecture

### Migration System Components

The migration system follows a clean separation of concerns:

```
Migration System
├── Commands (Console Layer)
│   ├── Migrate           - Run pending migrations
│   ├── MigrateRollback   - Rollback migrations
│   ├── MigrateFresh      - Drop all & re-migrate
│   └── MakeMigration     - Create migration files
│
├── Core Components
│   ├── Migration         - Base migration class
│   ├── MigrationResolver - Find & load migration files
│   ├── MigrationExecutor - Execute up/down methods
│   └── MigrationRepository - Track migration history
│
└── Database Layer
    └── migrations table  - Stores migration history
```

### 1. Migration Class

The base class for all migrations:

```php
abstract class Migration implements MigrationContract
{
    public protected(set) string $name;

    abstract public function up(): void;
    abstract public function down(): void;

    public function withName(string $name): static
    {
        return clone($this, ['name' => $name]);
    }
}
```

**Key Features:**
- Uses PHP 8.5's `protected(set)` for asymmetric visibility
- Immutable name via `withName()` using `clone()` with named arguments
- Simple contract: `up()` for applying, `down()` for rolling back

### 2. MigrationResolver

Finds and resolves migration files


### 3. MigrationExecutor

Executes migrations in the correct direction


### 4. MigrationRepository

Tracks migration history in the database


## Migration Commands

### 1. migrate

Runs all pending migrations:

**Usage:**
```bash
# Run all pending migrations
php bin/console.php migrate

# Run only 2 migrations
php bin/console.php migrate --step=2

```

### 2. migrate:rollback

Rolls back the last batch(es) of migrations

**Usage:**
```bash
# Rollback last batch
php artisan migrate:rollback

# Rollback last 3 batches
php artisan migrate:rollback --step=3
```

### 3. migrate:fresh

Drops all tables and re-runs all migrations:

**Usage:**
```bash
# Drop all tables and re-migrate
php artisan migrate:fresh

```

**Use Cases:**
- ✅ Development: Quick database reset
- ✅ Testing: Clean state for each test run
- ⚠️ Production: **NEVER** (use with extreme caution)

### 4. make:migration

Creates a new migration file

**Usage:**
```bash
# Create migration
php artisan make:migration create_users

# Output: 2024_01_15_103045_create_users_table.php
```

**Features:**
- Automatically adds `_table` suffix if missing
- Timestamp prefix for ordering: `YYYY_MM_DD_HHmmss_`
- Uses ClockFactory for testable timestamps (PSR-20 compatible)
- Configurable path via `config('database.migrations.path')`

## Migration File Structure

### Example Migration

```php
<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use Larafony\Framework\Database\Base\Migrations\Migration;
use Larafony\Framework\Database\Schema;
use Larafony\Framework\Database\Base\Schema\TableDefinition;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (TableDefinition $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('email', 255);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password', 255);
            $table->timestamps();
        });

        Schema::create('sessions', function (TableDefinition $table) {
            $table->string('id', 255)->primary();
            $table->foreignId('user_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('payload');
            $table->dateTime('last_activity')->currentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::drop('sessions');
        Schema::drop('users');
    }
};
```

**Best Practices:**
- Always implement `down()` for rollback support
- Drop tables in reverse order (foreign keys)
- Use Schema Builder, not raw SQL
- Keep migrations focused (one concern per migration)

## Batch System

Migrations are grouped into **batches** for organized rollback:

### How Batches Work

```sql
-- migrations table
+----+----------------------------------+-------+
| id | migration                        | batch |
+----+----------------------------------+-------+
| 1  | 2024_01_01_000000_create_users   | 1     |
| 2  | 2024_01_02_000000_create_posts   | 1     |
| 3  | 2024_01_03_000000_create_comments| 2     |
| 4  | 2024_01_04_000000_add_user_roles | 3     |
+----+----------------------------------+-------+
```

### Batch Scenarios

**Scenario 1: First Migration**
```bash
php artisan migrate
# Runs: create_users, create_posts (batch 1)
```

**Scenario 2: New Migrations**
```bash
php artisan migrate
# Runs: create_comments (batch 2)
```

**Scenario 3: Rollback One Batch**
```bash
php artisan migrate:rollback
# Rolls back: add_user_roles (batch 3)
```

**Scenario 4: Rollback Multiple Batches**
```bash
php artisan migrate:rollback --step=2
# Rolls back: add_user_roles (batch 3), create_comments (batch 2)
```

## Comparison with Other Frameworks

### Laravel vs Larafony

**Laravel:**
```php
// Migration class structure
class CreateUsersTable extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
}
```

**Larafony:**
```php
// Migration returns anonymous class
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (TableDefinition $table) {
            $table->id();
            $table->string('name')->length(255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::drop('users');
    }
};
```

### Key Differences

| Feature | Laravel | Larafony |
|---------|---------|----------|
| Class Structure | Named class | Anonymous class |
| Return Type | None | `: void` (strict) |
| String Columns | Length optional | Length required |
| Drop Method | `dropIfExists()` | `drop()` |
| Type Safety | Loose | Strict (`declare(strict_types=1)`) |
| Namespace | Auto-generated | Always `App\Database\Migrations` |

### Symfony (Doctrine Migrations)

**Symfony:**
```php
final class Version20240115103045 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE users');
    }
}
```

**Differences:**
- Uses raw SQL strings instead of Schema Builder
- Version-based naming instead of timestamps
- More complex configuration
- Multi-database support (PostgreSQL, Oracle, etc.)




This gracefully handles rolling back more steps than exist.

## Key Takeaways

1. **Anonymous Classes** - Migration files return anonymous classes for simplicity
2. **Batch System** - Group migrations for organized rollback
3. **Type Safety** - Strict types everywhere (`declare(strict_types=1)`)
4. **Clock Abstraction** - `ClockFactory` makes timestamps testable
5. **Grammar Pattern** - Reuses Schema Builder from Chapter 10
6. **Repository Pattern** - `MigrationRepository` abstracts database operations
7. **Resolver Pattern** - `MigrationResolver` handles file discovery and loading
8. **Executor Pattern** - `MigrationExecutor` runs up/down methods
9. **Command Separation** - Each command has single responsibility
10. **Pipe Operator** - PHP 8.5's `|>` for clean data transformations
11. **First-Class Callables** - `$this->runUp(...)` for cleaner code
13. **Laravel-Compatible** - Nearly identical API for easy migration

## Next Chapter

In **Chapter 13**, I'll implement an **ORM (Object-Relational Mapper)** to provide an elegant, ActiveRecord-style interface for interacting with database records, allowing you to work with database rows as PHP objects.

---

**Note:** This is a production-ready implementation, not tutorial code. Every component is fully tested, type-safe, and follows SOLID principles.
