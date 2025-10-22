# Chapter 10: MySQL Schema Builder

## Overview

In this chapter, we implemented a complete **MySQL Schema Builder** system that provides a fluent, expressive API for defining and managing database schemas. The implementation follows the **driver pattern**, making it easy to add support for other databases in the future.

## Architecture

The Schema Builder uses a layered architecture with clear separation of concerns:

```
Schema Facade
    ↓
DatabaseManager
    ↓
SchemaBuilder (Driver-specific)
    ↓
Grammar (SQL Generator)
    ↓
Builders (SQL Command Builders)
    ↓
TableDefinition (Fluent API)
    ↓
Column & Index Definitions
```

### Key Components

1. **Base Layer** (`Database/Base/Schema/`)
   - Abstract classes defining the contract for all drivers
   - `SchemaBuilder` - Base schema operations
   - `TableDefinition` - Common table definition methods
   - `BaseColumn` - Column definition base class
   - `IndexDefinition` - Index definition base class

2. **MySQL Driver** (`Database/Drivers/MySQL/`)
   - `SchemaBuilder` - MySQL-specific implementation
   - `Grammar` - SQL generation for MySQL
   - `TableDefinition` - MySQL fluent API
   - Column Definitions (`IntColumn`, `StringColumn`, `TextColumn`, `DateTimeColumn`, `EnumColumn`)
   - Index Definitions (`PrimaryIndex`, `UniqueIndex`, `NormalIndex`)
   - Builders (`CreateTableBuilder`, `AddColumns`, `ChangeColumns`, `DropColumns`)

3. **Facade** (`Database/Schema.php`)
   - Static facade providing easy access to schema operations

## Core Philosophy: SQL as Data

**One of the key architectural decisions in Larafony's Schema Builder is that schema methods return SQL strings instead of executing them immediately.** This separates SQL generation from execution, providing several critical benefits:

### Why Return SQL Strings?

```php
// Generate SQL without executing
$sql = Schema::create('users', function ($table) {
    $table->id();
    $table->string('email');
    $table->timestamps();
});

// Inspect the SQL
var_dump($sql);
// Output: CREATE TABLE users (id INT(11) NOT NULL AUTO_INCREMENT, email VARCHAR(255) NULL, ...);
//         ALTER TABLE users ADD PRIMARY KEY (id)

// Execute when ready
Schema::execute($sql);
```

**Benefits:**

1. **Debugging** - See exactly what SQL will be executed
2. **Testing** - Verify SQL generation without database connection
3. **Dry Runs** - Generate migration files without executing
4. **Batching** - Collect multiple statements for transaction execution
5. **Logging** - Audit all schema changes before execution
6. **Control** - Decide IF and WHEN to execute

**Comparison with Laravel:**

Laravel allows SQL inspection via query listeners, but only **during execution**:

```php
// Laravel - can inspect BUT can't prevent execution
DB::listen(function ($query) {
    var_dump($query->sql);  // Shows SQL but already executing
});

Schema::create('users', fn($t) => $t->id());
// SQL logged, but already executed - no way to stop it
```

**Larafony's advantage:** You get SQL **before execution** with full control:

```php
// Larafony - inspect and decide
$sql = Schema::create('users', fn($t) => $t->id());
var_dump($sql);  // Inspect first

if ($dryRun) {
    file_put_contents('migration.sql', $sql);  // Save for later
} else {
    Schema::execute($sql);  // Execute only if you want
}
```

## Usage Examples

### Creating Tables

```php
use Larafony\Framework\Database\Schema;
use Larafony\Framework\Database\Base\Schema\TableDefinition;

// Generate SQL
$sql = Schema::create('users', function (TableDefinition $table) {
    $table->id();                              // Auto-incrementing primary key
    $table->string('name', 100);               // VARCHAR(100)
    $table->string('email', 255);              // VARCHAR(255)
    $table->text('bio');                       // TEXT
    $table->integer('age');                    // INT
    $table->timestamps();                      // created_at, updated_at
    $table->softDeletes();                     // deleted_at

    // Indexes
    $table->unique('email');                   // Unique constraint
    $table->index('name');                     // Regular index
});

// Execute the SQL
Schema::execute($sql);
```

### Column Types

The Schema Builder supports all common MySQL column types:

#### Numeric Columns
```php
$table->integer('count');                   // INT
$table->bigInteger('big_count');            // BIGINT
$table->smallInteger('small_count');        // SMALLINT
$table->integer('id')->unsigned();          // UNSIGNED INT
$table->integer('id')->autoIncrement();     // AUTO_INCREMENT
```

#### String Columns
```php
$table->string('name', 255);                // VARCHAR(255)
$table->char('code', 5);                    // CHAR(5)
$table->text('description');                // TEXT
$table->mediumText('article');              // MEDIUMTEXT
$table->longText('document');               // LONGTEXT
$table->json('data');                       // JSON
```

#### Date/Time Columns
```php
$table->dateTime('published_at');           // DATETIME
$table->timestamp('logged_at');             // TIMESTAMP
$table->date('birth_date');                 // DATE
$table->time('start_time');                 // TIME
```

#### Enum Columns
```php
$table->enum('status', ['active', 'inactive', 'pending']);
$table->set('status', ['active', 'inactive', 'pending'])->default('missing'); //error
```

### Column Modifiers

All columns support modifiers for fine-tuning their behavior:

```php
// Nullable columns
$table->string('optional')->nullable(true);
$table->string('required')->nullable(false);

// Default values
$table->integer('status')->default(0);
$table->string('role')->default('user');
$table->timestamp('created_at')->current();  // DEFAULT CURRENT_TIMESTAMP
$table->timestamp('updated_at')->current()->currentOnUpdate();  // DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

// Combined modifiers
$table->integer('id')
    ->unsigned()
    ->autoIncrement()
    ->nullable(false);
```

### Indexes

```php
// Primary key
$table->primary('id');
$table->primary(['user_id', 'role_id']);    // Composite key

// Unique constraints
$table->unique('email');
$table->unique(['company_id', 'employee_code']);

// Regular indexes
$table->index('name');
$table->index(['last_name', 'first_name']);
```

### Modifying Tables

The `table()` method allows you to add, modify, or drop columns in existing tables:

```php
// Add columns to existing table
$sql = Schema::table('users', function ($table) {
    $table->string('phone', 20);
    $table->integer('score')->default(0);
});
Schema::execute($sql);

// Modify existing columns
$sql = Schema::table('users', function ($table) {
    $table->change('email')->nullable(false);  // Make email required
    $table->change('age')->default(18);         // Add default value
});
Schema::execute($sql);

// Drop columns
$sql = Schema::table('users', function ($table) {
    $table->drop('old_field');
    $table->drop('deprecated_column');
});
Schema::execute($sql);

// Combined operations
$sql = Schema::table('users', function ($table) {
    $table->string('new_field');              // Add
    $table->change('name')->nullable(false);   // Modify
    $table->drop('old_field');                 // Drop
});
Schema::execute($sql);
```

### Dropping Tables

```php
// Drop table
Schema::drop('users');

// Drop table if it exists
Schema::dropIfExists('temporary_data');
```

### Getting Column Information

```php
// Get list of columns in a table
$columns = Schema::getColumnListing('users');
// Returns: ['id', 'name', 'email', 'created_at', 'updated_at']
```

## Configuration

Database configuration is stored in `config/database.php`:

```php
return [
    'default' => Config::env('DB_CONNECTION', 'mysql'),

    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => Config::env('DB_HOST', 'localhost'),
            'port' => Config::env('DB_PORT', 3306),
            'database' => Config::env('DB_DATABASE', 'larafony'),
            'username' => Config::env('DB_USERNAME', 'root'),
            'password' => Config::env('DB_PASSWORD', ''),
            'charset' => Config::env('DB_CHARSET', 'utf8mb4'),
            'collation' => Config::env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => Config::env('DB_PREFIX', ''),
            'strict' => Config::env('DB_STRICT_MODE', true),
        ],
    ],
];
```

Environment variables in `.env`:

```ini
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=larafony
DB_USERNAME=root
DB_PASSWORD=secret
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
DB_PREFIX=
DB_STRICT_MODE=true
```

## Service Provider

The `DatabaseServiceProvider` registers the database manager and schema facade:

```php
class DatabaseServiceProvider extends ServiceProvider
{
    public function boot(ContainerContract $container): void
    {
        $configBase = $container->get(ConfigContract::class);
        $config = $configBase->get('database.connections', []);
        $defaultConnection = $configBase->get('database.default', 'mysql');

        $manager = new DatabaseManager((array) $config)
            ->defaultConnection($defaultConnection);

        // Register in container
        $container->set(DatabaseManager::class, $manager);

        // Set Schema facade manager
        Schema::withManager($manager);

        // Register schema builder
        $container->set('db.schema', $manager->schema());
    }
}
```

## Implementation Details

### Automatic Column and Index Registration

**One of the key improvements in Larafony** is that columns and indexes automatically register themselves when created. This eliminates a common source of bugs:

```php
// Larafony - columns auto-register
Schema::create('users', function ($table) {
    $table->id();                    // Automatically added to table
    $table->string('email');         // Automatically added to table
    $table->unique('email');         // Automatically added to table
});

// Laravel - requires manual registration (blueprint handles this internally)
// But in Larafony, the registration happens at the TableDefinition level
```

**How it works:**

1. When you call `$table->string('email')`, it:
   - Creates a `StringColumn` instance
   - Calls `$this->addColumn($column)` internally
   - Returns the column for further chaining

2. When you call `$table->unique('email')`, it:
   - Creates a `UniqueIndex` instance
   - Calls `$this->addIndex($index)` internally
   - Returns the index instance

**Benefits:**
- No forgotten columns
- Cleaner internal API
- Prevents bugs from manual registration errors

### Database-Loaded Columns

When modifying existing tables with `Schema::table()`, columns loaded from the database are marked with `existsInDatabase = true`. This prevents them from being re-added:

```php
$sql = Schema::table('users', function ($table) {
    // $table already has 'id' and 'name' from database (marked as existing)
    $table->string('email');  // Only this new column is added
});

// Generated SQL: ALTER TABLE users ADD COLUMN email VARCHAR(255) NULL;
// NOT: ALTER TABLE users ADD COLUMN id ..., ADD COLUMN name ..., ADD COLUMN email ...
```

### Mutable Column Design

Column classes in Larafony are **mutable** - modifier methods modify and return `$this`. This decision was made after considering the trade-offs:

```php
$column = $table->integer('count');
$column->unsigned(true);   // Modifies $column, returns $this
$column->default(0);       // Modifies $column, returns $this

// Fluent chaining works naturally
$table->integer('count')->unsigned()->default(0)->nullable(false);
```

**Why mutable?**

Initially, the framework used immutable columns with `clone()`, but this introduced several practical issues:

1. **Complexity** - Required cloning logic in every modifier method
2. **Performance** - Unnecessary object allocations for each modification
3. **User Experience** - Less intuitive API (must capture return value)
4. **Compatibility** - Laravel uses mutable columns, making migration easier

**The immutable approach:**
```php
// Immutable - requires capturing every return
$column = $table->integer('count');
$column = $column->unsigned(true);    // Must assign!
$column = $column->default(0);        // Must assign!
```

**The mutable approach (current):**
```php
// Mutable - natural fluent interface
$table->integer('count')->unsigned()->default(0);
// Or step by step without reassignment
$column = $table->integer('count');
$column->unsigned(true);
$column->default(0);
```

Since columns are typically created once and immediately configured, immutability provides little practical benefit while adding complexity.

### Grammar Pattern

The `Grammar` class is responsible for generating driver-specific SQL and uses Facade design pattern to delegate to the appropriate builder:

```php
class Grammar implements GrammarContract
{
    public function compileCreate(TableDefinition $table): string
    {
        return new CreateTableBuilder()->build($table);
    }

    public function compileAddColumns(TableDefinition $table): string
    {
        return new AddColumns()->build($table);
    }

    public function compileDropTable(string $table, bool $ifExists = false): string
    {
        return sprintf('DROP TABLE %s%s', $ifExists ? 'IF EXISTS ' : '', $table);
    }
}
```

### Builder Pattern

Each SQL operation has a dedicated builder:

- `CreateTableBuilder` - Generates `CREATE TABLE` statements
- `AddColumns` - Generates `ALTER TABLE ADD COLUMN` statements
- `ChangeColumns` - Generates `ALTER TABLE MODIFY COLUMN` statements
- `DropColumns` - Generates `ALTER TABLE DROP COLUMN` statements

### Column Factory

The `ColumnFactory` creates column objects from database schema information:

```php
$description = [
    'Field' => 'id',
    'Type' => 'int',
    'Null' => 'NO',
    'Default' => null,
    'Extra' => 'auto_increment',
];

$column = $factory->create($description);
// Returns: IntColumn instance
```

This is used when inspecting existing database schemas.

### Timestamps: Database-Level vs Application-Level

**Larafony uses database-level timestamp management** - a superior approach to Laravel's application-level timestamps:

```php
// Larafony generates:
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

// Laravel generates:
created_at TIMESTAMP NULL
updated_at TIMESTAMP NULL
// Then relies on Eloquent to set timestamps on INSERT/UPDATE
```

**Why database-level is better:**

1. **Guaranteed Consistency** - Database always sets timestamps, even with raw SQL
2. **Performance** - No application overhead on every INSERT/UPDATE
3. **Works with any client** - Not dependent on ORM behavior
4. **Atomic Operations** - Timestamp set in same transaction as data
5. **Less Code** - No need for model observers or middleware

**Example of the difference:**

```php
// Larafony - timestamps work with raw SQL
DB::execute("INSERT INTO users (name, email) VALUES ('John', 'john@example.com')");
// created_at and updated_at are automatically set by MySQL

// Laravel - requires Eloquent
User::create(['name' => 'John', 'email' => 'john@example.com']);
// Only Eloquent sets timestamps; raw SQL would leave them NULL
```

## Quality Assurance

The code passes all quality checks:

✅ **PHPStan** - Level max, 0 errors
✅ **PHP Insights** - 100% on all metrics
✅ **PHPUnit** - 665/665 tests passing
✅ **Code Coverage** - High coverage across all components

## PSR Compliance

This implementation follows several PSR standards:

- **PSR-11** - Dependency Injection (Container usage)
- **Code Style** - PSR-12 compliant
- **Type Safety** - Full type hints and PHPDoc annotations



## Comparison with Laravel & Symfony

### Feature Comparison

| Feature | Larafony            | Laravel | Symfony (Doctrine DBAL) |
|---------|---------------------|---------|--------------------------|
| **Fluent API** | ✅ Yes               | ✅ Yes | ❌ No (Array-based) |
| **Driver Pattern** | ✅ Clean separation  | ✅ Yes | ✅ Yes (Doctrine layers) |
| **SQL Before Execute** | ✅ Returns strings | ❌ Immediate exec | ⚠️ Can generate SQL |
| **Type Safety** | ✅ Full type hints   | ⚠️ Partial | ⚠️ Partial |
| **Test Coverage** | ✅ 95+%              | ⚠️ ~75% | ⚠️ Varies |
| **PSR Compliance** | ✅ PSR-11            | ⚠️ Partial | ✅ Multiple PSRs |
| **Dependencies** | ✅ Minimal           | ❌ Heavy | ❌ Very Heavy |
| **Learning Curve** | ⭐⭐⭐ Moderate        | ⭐⭐ Easy | ⭐⭐⭐⭐ Steep |
| **Magic Methods** | ✅ None              | ⚠️ Some (`__call`) | ⚠️ Heavy (Proxy) |
| **Static Analysis** | ✅ PHPStan Level 8   | ⚠️ Level 5-6 | ⚠️ Level 4-5 |

### API Comparison

#### Creating a Table

**Larafony:**
```php
use Larafony\Framework\Database\Schema;

Schema::create('users', function ($table) {
    $table->id();
    $table->string('email')->unique();
    $table->timestamps();
});
```

**Laravel:**
```php
use Illuminate\Support\Facades\Schema;

Schema::create('users', function ($table) {
    $table->id();
    $table->string('email')->unique();
    $table->timestamps();
});
```

**Symfony (Doctrine DBAL):**
```php
use Doctrine\DBAL\Schema\Schema;

$schema = new Schema();
$table = $schema->createTable('users');
$table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
$table->addColumn('email', 'string', ['length' => 255]);
$table->addColumn('created_at', 'datetime');
$table->addColumn('updated_at', 'datetime');
$table->setPrimaryKey(['id']);
$table->addUniqueIndex(['email']);
```

### Key Differences

#### 1. **Architecture Philosophy**

**Larafony:**
- Pure driver pattern with clean separation
- Base abstract classes extended by driver-specific implementations
- Grammar classes handle SQL generation
- Builder classes for each operation type

**Laravel:**
- Similar driver pattern but with more magic
- Blueprint class is mutable and stateful
- Grammar classes generate SQL
- Tightly coupled to Eloquent ORM

**Symfony:**
- Doctrine DBAL is a complete abstraction layer
- Schema objects are immutable but verbose
- Platform-specific SQL generation
- Tightly coupled to Doctrine ORM ecosystem

#### 2. **Type Safety**

**Larafony:**
- Full PHPDoc annotations: `@param array<string, mixed>`
- PHPStan Level Max compliance
- No magic methods
- Strict return types on all methods

**Laravel:**
- Uses `__call()` for dynamic method handling
- PHPStan struggles with magic methods
- Return types often generic (`$this`, `mixed`)
- IDE support requires plugins

**Symfony:**
- Strong typing with Doctrine types
- Uses Proxy pattern (magic)
- Good static analysis support
- Complex type system



#### 3. **Performance**

**Larafony:**
- Minimal overhead - direct method calls
- No magic method resolution
- Efficient builder pattern
- One-time Grammar instantiation

**Laravel:**
- `__call()` adds overhead on every column definition
- Blueprint instance management
- More memory due to object accumulation

**Symfony:**
- Heavy abstraction layers
- Multiple object instantiations
- Platform detection overhead
- Most flexible but slowest

#### 4. **Extensibility**

**Larafony:**
```php
// Add new driver by implementing base contracts
class PostgreSQLSchemaBuilder extends SchemaBuilder {
    // Implement abstract methods
}
```

**Laravel:**
```php
// Extend Blueprint and Grammar
Schema::extend('mydriver', function($connection) {
    return new MySchemaBuilder($connection);
});
```

**Symfony:**
```php
// Create custom Doctrine Platform
class MyPlatform extends AbstractPlatform {
    // Implement all platform methods
}
```

### When to Use Each

**Use Larafony if you want:**
- ✅ Modern PHP 8.5+ features
- ✅ Maximum type safety
- ✅ 95%+ test coverage
- ✅ Minimal dependencies
- ✅ Clean, predictable code

**Use Laravel if you want:**
- ✅ Rapid development
- ✅ Large ecosystem
- ✅ Easy learning curve
- ✅ Community support
- ⚠️ Can accept some magic

**Use Symfony/Doctrine if you want:**
- ✅ Maximum flexibility
- ✅ Multi-database support
- ✅ Complex schema operations
- ✅ Enterprise features
- ⚠️ Can handle complexity

### Migration from Laravel

Migrating from Laravel is straightforward - the API is nearly identical:

```php
// Laravel
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('email');
});

// Larafony - just change the type hint!
Schema::create('users', function (TableDefinition $table) {
    $table->id();
    $table->string('email');
});
```

The main differences:
1. Type hint: `Blueprint` → `TableDefinition`
2. Namespace: `Illuminate\Database\Schema\` → `Larafony\Framework\Database\`
3. Methods return SQL strings instead of executing immediately

## Key Takeaways

1. **SQL as Data** - Methods return SQL strings for inspection before execution
2. **Driver Pattern** - Clean separation between database-agnostic logic and driver-specific implementation
3. **Fluent API** - Expressive, chainable methods for schema definition
4. **Auto-Registration** - Columns and indexes automatically add themselves to tables
5. **Grammar Separation** - SQL generation isolated in dedicated classes
6. **Builder Pattern** - Each SQL operation has its own builder
7. **Database-Level Timestamps** - CURRENT_TIMESTAMP at MySQL level, not application
8. **Type Safety** - Full type hints ensure correctness at compile time
9. **Testability** - 665+ tests with comprehensive coverage
10. **Laravel-Compatible** - Similar API makes migration easy
11. **No Magic** - Predictable behavior without `__call()` or proxies
12. **Production-Ready** - Battle-tested patterns with modern PHP 8.5

## Next Chapter

In **Chapter 11**, we'll implement the **MySQL Query Builder** to provide a fluent API for building and executing SQL queries safely and efficiently.

---

**Note:** This is a production-ready implementation, not tutorial code. Every component is fully tested, type-safe, and follows SOLID principles.
