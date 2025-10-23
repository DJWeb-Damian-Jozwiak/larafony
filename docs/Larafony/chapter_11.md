# Chapter 11: Query Builder - Fluent SQL Query Construction

> Part of the Larafony Framework - A modern PHP 8.5 framework built from scratch
>
> 📚 Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)

## Overview

Chapter 11 introduces the **Query Builder** - a fluent, expressive interface for constructing and executing SQL queries programmatically. Building on the database connection layer from previous chapters, the Query Builder provides a type-safe, chainable API that protects against SQL injection while maintaining the flexibility of raw SQL.

The implementation follows a clean architecture pattern where abstract base classes define the fluent API contract, while driver-specific implementations handle SQL generation. This design allows the same query builder code to work across different database systems (MySQL, PostgreSQL, SQLite, etc.) without modification - each driver simply provides its own Grammar implementation to compile queries into the appropriate SQL dialect.

The Query Builder supports all essential query operations: SELECT with complex WHERE conditions (including nested clauses), JOINs (inner, left, right), ORDER BY, LIMIT/OFFSET, as well as INSERT, UPDATE, and DELETE operations. All queries use prepared statements with parameter binding for maximum security.

## Key Components

### Core Query Builder Classes

- **QueryBuilder (abstract)** - Base fluent API providing chainable methods for query construction (src/Larafony/Database/Base/Query/QueryBuilder.php:21)
- **QueryDefinition** - Immutable state container storing all query components (table, columns, wheres, joins, etc.) similar to TableDefinition in the Schema Builder (src/Larafony/Database/Base/Query/QueryDefinition.php:17)
- **MySQL\QueryBuilder** - Concrete MySQL implementation that builds clause objects and delegates to Grammar for SQL compilation (src/Larafony/Database/Drivers/MySQL/QueryBuilder.php:28)
- **Grammar** - Facade that delegates SQL compilation to specialized builders (SelectBuilder, InsertBuilder, UpdateBuilder, DeleteBuilder) (src/Larafony/Database/Drivers/MySQL/Query/Grammar.php:23)

### Query Clause Components

The framework uses the **Strategy Pattern** for different WHERE clause types, with specialized classes handling each variant:

- **WhereClause** - Base interface for all WHERE conditions (src/Larafony/Database/Base/Query/Clauses/Where/WhereClause.php)
- **Concrete WHERE implementations**: WhereBasic (column = value), WhereIn (column IN array), WhereNull (column IS NULL), WhereBetween (column BETWEEN x AND y), WhereLike (column LIKE pattern), WhereNested (grouped conditions with parentheses)
- **JoinClause** - Handles table joins with ON conditions (src/Larafony/Database/Base/Query/Clauses/JoinClause.php)
- **OrderByClause** - Sorting specifications (src/Larafony/Database/Base/Query/Clauses/OrderByClause.php)
- **LimitClause** - Pagination with limit and offset (src/Larafony/Database/Base/Query/Clauses/LimitClause.php)

### Grammar Builders

The Grammar uses specialized builders following **Single Responsibility Principle**:

- **SelectBuilder** - Compiles SELECT queries (src/Larafony/Database/Drivers/MySQL/Query/Grammar/Builders/SelectBuilder.php)
- **InsertBuilder** - Compiles INSERT queries (src/Larafony/Database/Drivers/MySQL/Query/Grammar/Builders/InsertBuilder.php)
- **UpdateBuilder** - Compiles UPDATE queries (src/Larafony/Database/Drivers/MySQL/Query/Grammar/Builders/UpdateBuilder.php)
- **DeleteBuilder** - Compiles DELETE queries (src/Larafony/Database/Drivers/MySQL/Query/Grammar/Builders/DeleteBuilder.php)
- **Component builders**: JoinBuilder, WhereBuilder, OrderByBuilder, LimitBuilder (assemble specific SQL fragments)

### Enums and Contracts

- **QueryType** - Enum defining query types: SELECT, INSERT, UPDATE, DELETE (src/Larafony/Database/Base/Query/Enums/QueryType.php)
- **JoinType** - Enum for JOIN types: INNER, LEFT, RIGHT (src/Larafony/Database/Base/Query/Enums/JoinType.php)
- **OrderDirection** - Enum for sort order: ASC, DESC (src/Larafony/Database/Base/Query/Enums/OrderDirection.php)
- **QueryBuilderContract** - Interface defining the fluent API (src/Larafony/Database/Base/Query/Contracts/QueryBuilderContract.php)
- **GrammarContract** - Interface for SQL compilation (src/Larafony/Database/Base/Query/Contracts/GrammarContract.php)
- **ClauseContract** - Interface for query clauses (src/Larafony/Database/Base/Query/Contracts/ClauseContract.php)

## PSR Standards Implemented

The Query Builder implementation adheres to key PSR standards:

- **PSR-1/PSR-12**: Code style and formatting - strict types, proper namespacing, consistent method naming
- **PSR-4**: Autoloading - organized namespace structure matching directory hierarchy
- **Implicit PDO prepared statements**: All queries use parameter binding for SQL injection protection (similar to PSR-0 security principles)

While there isn't a specific PSR standard for query builders, the implementation follows the security best practices that underpin modern PHP development standards.

## Usage Examples

### Basic SELECT Query

```php
<?php

use Larafony\Framework\Database\DatabaseManager;

// Get query builder from database manager
$db = app(DatabaseManager::class);
$builder = $db->table('users');

// Simple SELECT with columns
$users = $builder
    ->select(['id', 'name', 'email'])
    ->get();

// Returns: SELECT id, name, email FROM users
// Result: array of user rows
```

### WHERE Clauses

```php
<?php

// Basic WHERE
$activeUsers = $db->table('users')
    ->select(['*'])
    ->where('status', '=', 'active')
    ->where('age', '>', 18)
    ->get();
// Returns: SELECT * FROM users WHERE status = ? and age > ?

// OR conditions
$users = $db->table('users')
    ->where('status', '=', 'active')
    ->orWhere('verified', '=', true)
    ->get();
// Returns: SELECT * FROM users WHERE status = ? or verified = ?

// Nested WHERE groups
$users = $db->table('users')
    ->where('status', '=', 'active')
    ->whereNested(function ($query) {
        $query->where('age', '>', 18)
              ->orWhere('verified', '=', true);
    }, 'and')
    ->get();
// Returns: SELECT * FROM users WHERE status = ? and (age > ? or verified = ?)

// Special WHERE methods
$users = $db->table('users')
    ->whereIn('id', [1, 2, 3])
    ->whereNotNull('email_verified_at')
    ->whereBetween('age', [18, 65])
    ->whereLike('name', '%John%')
    ->get();
```

### JOINs

```php
<?php

use Larafony\Framework\Database\Base\Query\Enums\JoinType;

// Simple LEFT JOIN
$results = $db->table('users')
    ->select(['users.*', 'profiles.bio', 'profiles.avatar'])
    ->leftJoin('profiles', 'users.id', '=', 'profiles.user_id')
    ->where('users.status', '=', 'active')
    ->get();

// Multiple JOINs
$results = $db->table('orders')
    ->select(['orders.*', 'users.name', 'products.title'])
    ->leftJoin('users', 'orders.user_id', '=', 'users.id')
    ->leftJoin('products', 'orders.product_id', '=', 'products.id')
    ->get();

// Complex JOIN with closure
$results = $db->table('users')
    ->join('profiles', function ($join) {
        $join->on('users.id', '=', 'profiles.user_id')
             ->on('users.status', '=', 'profiles.status');
    })
    ->get();
```

### Ordering and Pagination

```php
<?php

use Larafony\Framework\Database\Base\Query\Enums\OrderDirection;

// ORDER BY
$users = $db->table('users')
    ->orderBy('created_at', OrderDirection::DESC)
    ->orderBy('name', OrderDirection::ASC)
    ->get();

// Convenience methods
$recentUsers = $db->table('users')->latest()->get(); // ORDER BY created_at DESC
$oldestUsers = $db->table('users')->oldest()->get(); // ORDER BY created_at ASC

// Pagination with LIMIT and OFFSET
$page2 = $db->table('users')
    ->limit(20)
    ->offset(20)
    ->get();
```

### INSERT, UPDATE, DELETE

```php
<?php

// INSERT
$success = $db->table('users')->insert([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'status' => 'active'
]);
// Returns: true on success

// INSERT and get ID
$userId = $db->table('users')->insertGetId([
    'name' => 'Jane Smith',
    'email' => 'jane@example.com'
]);
// Returns: last insert ID as string

// UPDATE
$affectedRows = $db->table('users')
    ->where('id', '=', 1)
    ->update(['status' => 'inactive']);
// Returns: number of rows updated

// DELETE
$deletedRows = $db->table('users')
    ->where('status', '=', 'inactive')
    ->whereNull('last_login_at')
    ->delete();
// Returns: number of rows deleted
```

### Advanced Examples

```php
<?php

// Complex query combining multiple features
$results = $db->table('users')
    ->select(['users.id', 'users.name', 'COUNT(orders.id) as order_count'])
    ->leftJoin('orders', 'users.id', '=', 'orders.user_id')
    ->where('users.status', '=', 'active')
    ->whereNested(function ($query) {
        $query->where('users.age', '>', 18)
              ->orWhere('users.verified', '=', true);
    }, 'and')
    ->whereNotNull('users.email_verified_at')
    ->orderBy('order_count', OrderDirection::DESC)
    ->limit(10)
    ->offset(20)
    ->get();

// Get single record
$user = $db->table('users')
    ->where('email', '=', 'john@example.com')
    ->first(); // Returns single row or null

// Count records
$activeUserCount = $db->table('users')
    ->where('status', '=', 'active')
    ->count();

// Debug queries
$sql = $db->table('users')
    ->where('status', '=', 'active')
    ->toSql(); // Returns SQL with ? placeholders

$rawSql = $db->table('users')
    ->where('status', '=', 'active')
    ->toRawSql(); // Returns SQL with actual values (for debugging only!)
```

## Comparison with Other Frameworks

| Feature | Larafony | Laravel | Symfony (Doctrine DBAL) |
|---------|----------|---------|--------------------------|
| **API Style** | Fluent, chainable methods | Fluent, chainable methods | Fluent, chainable methods |
| **Base Architecture** | Abstract base class + driver implementations | Single implementation with Grammar classes | QueryBuilder with platform-specific SQL |
| **SQL Generation** | Driver-specific Grammar with specialized builders | Grammar classes per database driver | Platform classes with SQL generation methods |
| **WHERE Clauses** | Strategy pattern - separate classes per type | Array-based storage with Grammar compilation | Method calls build internal state |
| **Nested WHERE** | `whereNested()` with closure | `where()` with closure | `where()` with `CompositeExpression` |
| **Parameter Binding** | Automatic via prepared statements | Automatic via prepared statements | Manual `setParameter()` required |
| **State Management** | `QueryDefinition` object (immutable) | Array properties in builder | Internal state arrays |
| **Query Inspection** | `toSql()` and `toRawSql()` | `toSql()` and `toRawSql()` | `getSQL()` and `getParameters()` |
| **Type Safety** | PHP 8.5 enums for types, directions | String constants | Integer constants |
| **PSR Compliance** | PSR-1, PSR-4, PSR-12 | PSR-1, PSR-4, PSR-12 | PSR-1, PSR-4, PSR-12 |
| **Approach** | From-scratch, educational, SOLID | Battle-tested, feature-rich | Enterprise-grade ORM integration |
| **Dependencies** | Minimal - PSR packages only | Laravel dependencies | Doctrine ORM/DBAL ecosystem |

### Key Differences

**Larafony's Unique Approach:**
- **QueryDefinition as State Container**: Separates query state from builder logic, making the query immutable and easier to test. Similar to how TableDefinition works in the Schema Builder.
- **Strategy Pattern for WHERE Clauses**: Each WHERE type (basic, in, null, between, like, nested) is a separate class implementing a common interface. This follows SOLID principles more strictly than Laravel's approach of storing WHERE conditions as arrays.
- **Specialized Grammar Builders**: Instead of one large Grammar class, Larafony splits SQL compilation into SelectBuilder, InsertBuilder, UpdateBuilder, and DeleteBuilder - each handling one query type.
- **PHP 8.5 Enums**: Uses native enums (QueryType, JoinType, OrderDirection) for type safety instead of string constants.
- **Educational Focus**: Code is written to teach design patterns and SOLID principles, making architecture decisions explicit rather than optimized for performance.

**Laravel's Approach:**
- Stores all query components as arrays within the builder
- Single Grammar class per driver with all compilation logic
- More mature with extensive optimization and edge case handling
- Rich ecosystem of query builder extensions (subqueries, unions, JSON operations, etc.)

**Symfony Doctrine DBAL:**
- Tighter integration with Doctrine ORM
- Explicit parameter binding using `setParameter()`
- Platform-specific SQL via `AbstractPlatform` classes
- More enterprise-focused with comprehensive transaction support

---

📚 **Learn More:** This implementation is explained in detail with step-by-step tutorials, tests, and best practices at [masterphp.eu](https://masterphp.eu)
