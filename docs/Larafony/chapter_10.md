# Chapter 10: Database Layer & MySQL Schema Builder

> Part of the Larafony Framework - A modern PHP 8.5 framework built from scratch
>
> 📚 Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)

## Overview

Chapter 10 introduces a complete database abstraction layer with a fluent Schema Builder system for MySQL. The implementation provides an expressive, type-safe API for creating and manipulating database schemas without writing raw SQL. Following Larafony's PSR-first philosophy, the database layer abstracts PDO connections while maintaining full control over schema operations.

The architecture separates SQL generation from execution - schema methods return SQL strings rather than executing immediately, giving developers complete visibility and control over database operations. This "SQL as data" approach enables inspection, logging, testing, and batch execution of schema changes.

The implementation uses a driver pattern with base abstractions and MySQL-specific implementations, making it straightforward to add support for PostgreSQL, SQLite, and other databases in future chapters.

## Key Components

### Database Management

- **DatabaseManager** - Central connection manager and schema builder factory (with helper classes: Connection pooling, driver resolution, configuration management)
- **Schema** - Static facade providing clean API for schema operations
- **DatabaseServiceProvider** - Service provider for container registration

### Schema Builder Core

- **SchemaBuilder (Base)** - Abstract base defining schema operation contracts
- **SchemaBuilder (MySQL)** - MySQL-specific implementation with Grammar integration
- **TableDefinition** - Fluent API for defining table structures with columns and indexes

### Column System

- **BaseColumn** - Abstract column with state tracking (modified, deleted, existsInDatabase flags)
- **Column Types**: IntColumn, StringColumn, TextColumn, DateTimeColumn, EnumColumn
- **ColumnFactory** - Factory for creating column instances from database descriptions

### Index System

- **IndexDefinition** - Base index abstraction
- **Index Types**: PrimaryIndex, UniqueIndex, NormalIndex

### SQL Generation

- **Grammar** - Compiles schema definitions into MySQL-specific SQL statements
- **Builders**: CreateTableBuilder, AddColumns, ChangeColumns, DropColumns
- **DatabaseInfo** - Introspects existing database schema for alterations

## PSR Standards Implemented

While this chapter doesn't directly implement a specific PSR, it follows PSR design principles:

- **PSR-11 Compatibility**: DatabaseManager integrates with the PSR-11 container via DatabaseServiceProvider
- **PSR-3 Ready**: Architecture supports adding PSR-3 logging for query tracking
- **Type Safety**: Leverages PHP 8.5 features (property hooks, asymmetric visibility) for type-safe APIs

## New PHP 8.5 Features Used

### Asymmetric Visibility

```php
// Read-only public access, write only within class
public protected(set) array $columns = [];
public protected(set) array $indexes = [];
public private(set) PDO $connection;
```

### Property Hooks

```php
// Virtual property computed from actual data
public array $columnNames {
    get => array_keys($this->columns);
}
```

### #[NoDiscard] Attribute

```php
// Compiler warning if return value is ignored
#[\NoDiscard]
public function create(string $table, Closure $callback): string
```

### #[SensitiveParameter] Attribute

```php
// Prevents passwords from appearing in stack traces
public function __construct(
    #[SensitiveParameter]
    private readonly ?string $password = null,
)
```

## Usage Examples

### Basic Table Creation

```php
<?php

use Larafony\Framework\Database\Schema;

// Create a simple users table
$sql = Schema::create('users', function ($table) {
    $table->id();                              // Auto-increment primary key
    $table->string('name')->nullable(false);   // VARCHAR(255) NOT NULL
    $table->string('email');                   // VARCHAR(255) NULL
    $table->timestamps();                      // created_at, updated_at
});

// Execute the SQL
Schema::execute($sql);

// Generated SQL:
// CREATE TABLE users (
//   id INT(11) NOT NULL AUTO_INCREMENT,
//   name VARCHAR(255) NOT NULL,
//   email VARCHAR(255) NULL,
//   created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
//   updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
//   PRIMARY KEY (id)
// );
```

### Advanced Table with Indexes

```php
<?php

use Larafony\Framework\Database\Schema;

$sql = Schema::create('posts', function ($table) {
    $table->id(); //primary key
    $table->string('title', 200)->nullable(false);
    $table->text('content');
    $table->string('slug', 255)->nullable(false);
    $table->integer('user_id')->nullable(false);
    $table->integer('views')->default(0);
    $table->timestamp('published_at')->nullable(true);
    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->unique('slug');                    // Unique constraint
    $table->index('user_id');                  // Normal index
    $table->index(['published_at', 'views']);  // Composite index
});

Schema::execute($sql);
```

### Altering Existing Tables

```php
<?php

use Larafony\Framework\Database\Schema;

// Add new columns to existing table
$sql = Schema::table('users', function ($table) {
    $table->string('phone', 20);
    $table->date('birth_date');
    $table->integer('status')->default(1);
});

Schema::execute($sql);

// Modify existing columns
$sql = Schema::table('users', function ($table) {
    $table->change('name')->nullable(false);   // Make name required
    $table->change('email')->nullable(false);  // Make email required
});

Schema::execute($sql);

// Drop columns
$sql = Schema::table('users', function ($table) {
    $table->drop('phone');
    $table->drop('status');
});

Schema::execute($sql);
```

### Inspect Generated SQL Before Execution

```php
<?php

use Larafony\Framework\Database\Schema;

// Generate SQL without executing
$createSql = Schema::create('products', function ($table) {
    $table->id();
    $table->string('name');
    $table->integer('price');
});

// Inspect the SQL
echo $createSql;  // See exactly what will be executed

// Log it (for audit/debugging)
$logger->info('Creating products table', ['sql' => $createSql]);

// Execute when ready
Schema::execute($createSql);
```

### Using DatabaseManager Directly

```php
<?php

use Larafony\Framework\Database\DatabaseManager;

$config = [
    'mysql' => [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'larafony',
        'username' => 'root',
        'password' => 'secret',
        'charset' => 'utf8mb4',
    ],
];

$manager = new DatabaseManager($config);

// Get connection
$connection = $manager->connection('mysql');

// Get schema builder
$schema = $manager->schema('mysql');

// Use schema builder
$sql = $schema->create('categories', function ($table) {
    $table->id();
    $table->string('name');
});

$schema->execute($sql);
```

## Comparison with Other Frameworks

| Feature | Larafony | Laravel | Symfony |
|---------|----------|---------|---------|
| **Schema Definition** | Fluent closures with TableDefinition | Blueprint class with fluent API | Doctrine DBAL SchemaManager |
| **SQL Execution** | Returns SQL string, manual execution | Immediate execution | Immediate execution via Platform |
| **PSR Compliance** | PSR-11 container integration | Custom container | Full PSR compliance |
| **Approach** | SQL as data (inspect before execute) | Automatic execution | Object-based schema comparison |
| **Column Types** | 15+ types (Int, String, Text, DateTime, Enum) | 40+ types with modifiers | Platform-agnostic column types |
| **Index Management** | Primary, Unique, Normal indexes | All index types + foreign keys | Full constraint support |
| **Driver Pattern** | Base abstractions + MySQL driver | Built-in multi-database support | Doctrine Platform abstraction |
| **PHP Features** | PHP 8.5 (property hooks, asymmetric visibility) | PHP 8.2+ features | PHP 8.1+ features |
| **Migration System** | Manual (Chapter 10 focus: Schema) | Built-in versioned migrations | Doctrine Migrations package |
| **Testing** | Returns SQL strings (easy to assert) | Database interactions (requires DB) | Schema comparison objects |

**Key Differences:**

- **SQL Visibility**: Larafony returns SQL strings for inspection/logging before execution, while Laravel/Symfony execute immediately. This makes Larafony's approach more transparent and testable.

- **Separation of Concerns**: Larafony strictly separates SQL generation (SchemaBuilder) from execution (Connection), enabling better testing, logging, and batch operations.

- **Type Safety**: Larafony leverages PHP 8.5's latest features (property hooks, asymmetric visibility) for compile-time safety, while Laravel/Symfony support broader PHP version ranges.

- **Driver Architecture**: Larafony uses explicit base/driver separation from the start, making the abstraction clear. Laravel's Blueprint abstracts internally, while Symfony uses Doctrine's complex Platform system.

- **Testing Philosophy**: Larafony's SQL-as-data approach allows testing schema logic without database connections (assert SQL strings). Laravel typically requires database interactions, though it supports pretending.

- **Configuration**: Laravel uses migration files with timestamps, Symfony uses entity annotations/attributes with migration generation, Larafony focuses on programmatic schema building (migrations come in future chapters).

## Real World Integration

This chapter's features are demonstrated in the demo application with database configuration and service provider integration.

### Demo Application Changes

The demo application was updated to:
1. Add database configuration file with environment-based settings
2. Register DatabaseServiceProvider in the application bootstrap
3. Configure MySQL connection parameters via .env file

### File Structure

```
demo-app/
├── config/
│   └── database.php          # Database connections configuration
├── bootstrap/
│   └── console_app.php       # Registers DatabaseServiceProvider
├── .env                      # Database credentials (MySQL connection)
└── .env.example              # Example environment variables
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
    |
    | The default connection is used when no connection name is specified
    | when calling Schema or DatabaseManager methods.
    |
    */
    'default' => EnvReader::read('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Each connection configuration follows the driver pattern, allowing
    | you to configure multiple databases and switch between them.
    |
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

1. **Environment-Based Configuration**: Uses EnvReader (from Chapter 9) to read database credentials from .env file with sensible defaults
2. **Default Connection**: Specifies which connection to use when none is explicitly provided
3. **Connection Array**: Defines multiple database connections (currently MySQL, expandable to PostgreSQL, SQLite, etc.)
4. **Driver Pattern**: Each connection specifies a 'driver' key that DatabaseManager uses to instantiate the correct Connection class
5. **Type Safety**: Explicit type casting for port (int) and strict mode (bool) ensures correct types
6. **Standard Settings**: Includes common MySQL settings like charset (utf8mb4 for full Unicode support), collation, table prefix, and storage engine

**File: `demo-app/bootstrap/console_app.php`** (changes)

```php
<?php

declare(strict_types=1);

use Larafony\Framework\Config\ServiceProviders\ConfigServiceProvider;
use Larafony\Framework\Console\ServiceProviders\ConsoleServiceProvider;
use Larafony\Framework\Database\ServiceProviders\DatabaseServiceProvider;  // NEW
use Larafony\Framework\ErrorHandler\ServiceProviders\ErrorHandlerServiceProvider;
use Larafony\Framework\Http\ServiceProviders\HttpServiceProvider;

/** @var \Larafony\Framework\Foundation\Application $app */

$app->withServiceProviders([
    ErrorHandlerServiceProvider::class,
    HttpServiceProvider::class,
    ConfigServiceProvider::class,
    DatabaseServiceProvider::class,    // Registers DatabaseManager and Schema facade
    ConsoleServiceProvider::class,
]);

return $app;
```

**What's happening here:**

1. **Service Provider Registration**: DatabaseServiceProvider is added to the application's service provider list
2. **Boot Order**: Registered after ConfigServiceProvider (dependency) and before ConsoleServiceProvider
3. **Automatic Setup**: When the application boots, DatabaseServiceProvider reads config/database.php and sets up the Schema facade

**File: `demo-app/.env`** (example additions)

```env
# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=larafony
DB_USERNAME=root
DB_PASSWORD=secret
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

**What's happening here:**

1. **Environment Isolation**: Database credentials are kept out of version control (using .env)
2. **Local Development**: Developers can use different database settings without modifying code
3. **Production Ready**: In production, these values would be set via environment variables or secrets management

### How It All Works Together

**Service Provider Boot Process (DatabaseServiceProvider.php:15-32)**:

```php
public function boot(ContainerContract $container): void
{
    // 1. Get ConfigContract from container (registered by ConfigServiceProvider)
    $configBase = $container->get(ConfigContract::class);

    // 2. Read database configuration from config/database.php
    $config = $configBase->get('database.connections', []);
    $defaultConnection = $configBase->get('database.default', 'mysql');

    // 3. Create DatabaseManager with connections config
    $manager = new DatabaseManager((array) $config)
        ->defaultConnection($defaultConnection);

    // 4. Register DatabaseManager in container (for dependency injection)
    $container->set(DatabaseManager::class, $manager);

    // 5. Initialize Schema facade with the manager
    Schema::withManager($manager);

    // 6. Register schema builder in container (for injection)
    $container->set('db.schema', $manager->schema());
}
```

**Step-by-step explanation:**

1. **Dependency Resolution**: Gets ConfigContract from container (which reads config/database.php)
2. **Configuration Loading**: Extracts connections array and default connection name
3. **Manager Creation**: Creates DatabaseManager instance with all connection configurations
4. **Container Registration**: Registers manager in container so it can be injected into services
5. **Facade Initialization**: Connects Schema static facade to the DatabaseManager instance
6. **Schema Builder Registration**: Makes the schema builder available via container for dependency injection

### Running the Demo

```bash
# 1. Set up your database credentials
cd demo-app
cp .env.example .env
# Edit .env with your MySQL credentials

```

**Example test script (`demo-app/test-schema.php`):**

```php
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/console_app.php';

use Larafony\Framework\Database\Schema;

// Schema facade is now available!
$sql = Schema::create('demo_users', function ($table) {
    $table->id();
    $table->string('name')->nullable(false);
    $table->string('email')->nullable(false);
    $table->timestamps();

    $table->unique('email');
});

echo "Generated SQL:\n";
echo $sql . "\n\n";

// Execute it
echo "Executing...\n";
Schema::execute($sql);
echo "Table created successfully!\n";

// Get column listing
$columns = Schema::getColumnListing('demo_users');
echo "\nColumns: " . implode(', ', $columns) . "\n";
```

**Expected output:**

```
Generated SQL:
CREATE TABLE demo_users (
  id INT(11) NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY demo_users_email_unique (email)
);

Executing...
Table created successfully!

Columns: id, name, email, created_at, updated_at
```

### Interactive Database Setup

The framework includes a `database:connect` command that interactively configures database credentials and automatically updates the `.env` file:

```bash
php bin/larafony database:connect
```

**Features:**

- **Interactive prompts** - Asks for host, port, database, username, and password with sensible defaults
- **Password security** - Uses `secret()` input to hide password characters (Unix/Linux/macOS)
- **Connection validation** - Tests the connection before saving credentials
- **Auto-retry** - If connection fails, prompts again with helpful error messages
- **Exit code signaling** - Returns exit code `2` when credentials are updated (useful for orchestrator commands)

**Example interaction:**

```
Configure database connection
Enter host [127.0.0.1]: ↵
Enter port [3306]: ↵
Enter database [larafony]: my_app↵
Enter username [root]: ↵
Enter password: ******* (hidden)
Database connected successfully!
```

The command automatically updates `.env` with `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` values.

### Key Takeaways

- **Configuration Layer Integration**: Database configuration uses the Config system from Chapter 9 (EnvReader), demonstrating how framework components build on each other
- **Service Provider Pattern**: DatabaseServiceProvider follows the same pattern as other providers, making the architecture consistent
- **Facade Pattern**: Schema facade provides static access while DatabaseManager handles the actual logic - clean separation of concerns
- **Environment-Based Setup**: .env file keeps credentials secure and enables different configurations per environment
- **Type Safety Throughout**: Configuration values are explicitly cast to correct types (int, bool) preventing runtime errors
- **Real-World Ready**: The demo shows exactly how you'd use the Schema Builder in an actual application, not just isolated examples

This integration demonstrates Larafony's philosophy: each component is designed to work seamlessly with others while maintaining clear boundaries and responsibilities.

---

📚 **Learn More:** This implementation is explained in detail with step-by-step tutorials, tests, and best practices at [masterphp.eu](https://masterphp.eu)
