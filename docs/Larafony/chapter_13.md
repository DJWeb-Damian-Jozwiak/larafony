# Chapter 13: Object-Relational Mapper (ORM) with Active Record Pattern

> Part of the Larafony Framework - A modern PHP 8.5 framework built from scratch
>
> 📚 Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)

## Overview

This chapter introduces a complete Object-Relational Mapper (ORM) implementing the Active Record pattern, allowing developers to interact with databases using intuitive object-oriented syntax instead of raw SQL queries. The ORM bridges the gap between object-oriented PHP code and relational databases, automatically translating model operations into SQL statements.

The implementation features a sophisticated property change tracking system using PHP 8.5's asymmetric visibility (`public protected(set)`, `public private(set)`), enabling the framework to detect which properties have been modified and generate efficient UPDATE queries. Models automatically determine whether to INSERT (new records) or UPDATE (existing records) when `save()` is called, eliminating manual state management.

The ORM supports four relationship types (HasMany, BelongsTo, BelongsToMany, HasManyThrough) defined entirely through PHP attributes, avoiding configuration files or annotations. Relationships are lazily loaded and use the powerful `ModelQueryBuilder` wrapper around the base query builder, providing a fluent API for filtering, ordering, and retrieving related records. The system includes intelligent pluralization for convention-based table naming and a factory pattern for instantiating relationship objects from attribute metadata.

## Key Components

### Core ORM Classes

- **Model** - Abstract base class for all database entities, implementing Active Record pattern with property change tracking, automatic INSERT/UPDATE detection, relationship loading, type casting, and query builder integration
- **ModelQueryBuilder** - Fluent query builder wrapper for models, providing chainable methods (`where()`, `orderBy()`, `with()`, etc.) and automatic hydration of results into model instances
- **DB** - Static facade for database operations providing convenient access to query builder for raw queries (similar to Laravel's DB facade)

### Property Management

- **PropertyObserver** - Tracks changed properties for dirty checking, determining if model is new (`is_new` property based on presence of primary key), and converting complex types to storable formats using `toString()` method
- **EntityManager** - Coordinates save operations by delegating to `EntityInserter` (for new records) or `EntityUpdater` (for existing records based on `PropertyObserver::is_new`)
- **EntityInserter** - Handles INSERT operations for new models (with helper: generates SQL from changed properties)
- **EntityUpdater** - Handles UPDATE operations for existing models (with helper: generates SQL from changed properties only)

### Relationships System

- **Relation** - Abstract base class defining relationship contract with `addConstraints()` method for applying relationship-specific query constraints
- **HasMany** - One-to-many relationship (e.g., User has many Posts)
- **BelongsTo** - Inverse of one-to-many (e.g., Post belongs to User)
- **BelongsToMany** - Many-to-many with pivot table (e.g., User belongs to many Roles), includes `attach()`, `detach()`, `sync()` methods for pivot management
- **HasManyThrough** - Indirect relationship through intermediate model (e.g., Country has many Posts through Users)
- **RelationFactory** - Creates relationship instances from attribute metadata using factory pattern
- **RelationDecorator** - Lazy-loads and caches relationship instances, decorating model with relationship access

### Relationship Attributes

- `#[HasMany]` - Defines one-to-many relationship on model property
- `#[BelongsTo]` - Defines inverse one-to-many relationship
- `#[BelongsToMany]` - Defines many-to-many with pivot table configuration
- `#[HasManyThrough]` - Defines indirect relationship through intermediate model

### Supporting Infrastructure

- **Pluralizer** - Intelligent English pluralization with irregular forms (person→people, mouse→mice, etc.) for convention-based table naming
- **MakeModel** command - Generates model classes from stub template with automatic table name pluralization

## PSR Standards Implemented

The ORM layer builds on existing PSR compliance established in previous chapters:

- **PSR-11**: Models use container for dependency injection of database connections and services
- **PSR-4**: Autoloading follows strict namespace conventions (`Larafony\Framework\Database\ORM\*`)

While ORMs don't have a dedicated PSR standard, the implementation follows PSR design principles: dependency injection, immutability where appropriate, and type safety throughout.

## New Attributes

This chapter introduces four relationship attributes for defining model associations:

- `#[HasMany(related: Post::class, foreign_key: 'user_id', local_key: 'id')]` - One-to-many relationship
- `#[BelongsTo(related: User::class, foreign_key: 'user_id', local_key: 'id')]` - Inverse one-to-many
- `#[BelongsToMany(related: Role::class, pivot_table: 'role_user', foreign_pivot_key: 'user_id', related_pivot_key: 'role_id')]` - Many-to-many
- `#[HasManyThrough(related: Post::class, through: User::class, first_key: 'country_id', second_key: 'user_id', local_key: 'id', second_local_key: 'id')]` - Indirect relationship

These attributes eliminate configuration files and make relationships self-documenting directly in the model code.

## Usage Examples

### Basic Example: Creating and Saving Models

```php
<?php

use Larafony\Framework\Database\ORM\Model;

// Define a User model
class User extends Model
{
    public string $table { get => 'users'; }

    public string $name {
        get => $this->name;
        set {
            $this->name = $value;
            $this->markPropertyAsChanged('name'); // Track changes
        }
    }

    public string $email {
        get => $this->email;
        set {
            $this->email = $value;
            $this->markPropertyAsChanged('email');
        }
    }
}

// Create and save a new user (INSERT)
$user = new User();
$user->name = 'John Doe';
$user->email = 'john@example.com';
$user->save(); // Executes: INSERT INTO users (name, email) VALUES ('John Doe', 'john@example.com')

// Update existing user (UPDATE)
$existingUser = User::query()->where('id', '=', 1)->first();
$existingUser->name = 'Jane Doe'; // Only name is marked as changed
$existingUser->save(); // Executes: UPDATE users SET name = 'Jane Doe' WHERE id = 1
// Note: Only changed properties are included in UPDATE query
```

### Advanced Example: Relationships with Attributes

```php
<?php

use Larafony\Framework\Database\ORM\Model;
use Larafony\Framework\Database\ORM\Attributes\HasMany;
use Larafony\Framework\Database\ORM\Attributes\BelongsTo;
use Larafony\Framework\Database\ORM\Attributes\BelongsToMany;

class User extends Model
{
    public string $table { get => 'users'; }

    public string $name { get => $this->name; set { /* ... */ } }
    public string $email { get => $this->email; set { /* ... */ } }

    // One-to-many: User has many posts
    #[HasMany(
        related: Post::class,
        foreign_key: 'user_id',
        local_key: 'id'
    )]
    public array $posts { get => $this->relations->posts; }

    // Many-to-many: User belongs to many roles
    #[BelongsToMany(
        related: Role::class,
        pivot_table: 'role_user',
        foreign_pivot_key: 'user_id',
        related_pivot_key: 'role_id'
    )]
    public array $roles { get => $this->relations->roles; }
}

class Post extends Model
{
    public string $table { get => 'posts'; }

    public string $title { get => $this->title; set { /* ... */ } }
    public string $content { get => $this->content; set { /* ... */ } }
    public int $user_id { get => $this->user_id; set { /* ... */ } }

    // Inverse: Post belongs to user
    #[BelongsTo(
        related: User::class,
        foreign_key: 'user_id',
        local_key: 'id'
    )]
    public User $user { get => $this->relations->user; }
}

// Usage: Access relationships
$user = User::query()->where('id', '=', 1)->first();

// Lazy-loaded relationship (executes query on first access)
$posts = $user->posts; // SELECT * FROM posts WHERE user_id = 1
foreach ($posts as $post) {
    echo $post->title;
}

// Access inverse relationship
$post = Post::query()->where('id', '=', 1)->first();
$author = $post->user; // SELECT * FROM users WHERE id = {post->user_id}
echo $author->name;

// Many-to-many operations
$user->roles; // Get all roles
// Attach roles to user
$roleRelation = $user->relations->getRoleRelation(); // Get BelongsToMany instance
$roleRelation->attach([1, 2, 3]); // INSERT INTO role_user (user_id, role_id) VALUES ...

// Detach specific roles
$roleRelation->detach([2]); // DELETE FROM role_user WHERE user_id = 1 AND role_id IN (2)

// Sync roles (detach all, attach new)
$roleRelation->sync([1, 3, 4]); // Removes all, then adds [1, 3, 4]
```

### Query Builder Integration

```php
<?php

// Fluent query building with ModelQueryBuilder
$users = User::query()
    ->where('email', 'like', '%@example.com')
    ->where('created_at', '>', '2025-01-01')
    ->orderBy('name', OrderDirection::ASC)
    ->limit(10)
    ->get(); // Returns array of User instances

// First or null
$user = User::query()
    ->where('email', '=', 'john@example.com')
    ->first(); // Returns User instance or null

// Count records
$count = User::query()
    ->where('status', '=', 'active')
    ->count(); // Returns int

// Complex queries with joins
$posts = Post::query()
    ->join('users', 'posts.user_id', '=', 'users.id')
    ->where('users.status', '=', 'active')
    ->orderBy('posts.created_at', OrderDirection::DESC)
    ->get();
```

## Implementation Details

### PHP 8.5 Features Showcase

The ORM system extensively uses cutting-edge PHP features:

**1. Asymmetric Visibility (PHP 8.4)**

Models use `public protected(set)` and `public private(set)` for controlled property access:

```php
// In Model class:
public protected(set) string $primary_key_name = 'id';
// Public read, protected write (only Model and subclasses can set)

public private(set) PropertyObserver $observer;
// Public read, private write (only Model can set)

// In PropertyObserver:
public private(set) array $changedProperties = [];
// Exposed publicly for reading, but only PropertyObserver can modify
```

This eliminates the need for getter/setter boilerplate while maintaining encapsulation.

**2. Property Hooks (PHP 8.4)**

Models use property hooks for automatic change tracking:

```php
public int|string $id {
    get => $this->id;
    set {
        $this->id = $value;
        $this->markPropertyAsChanged('id'); // Automatic dirty tracking
    }
}

public bool $is_new {
    get => $this->observer->is_new; // Computed property from observer
}
```

**3. First-Class Callable Syntax (PHP 8.1)**

Used throughout for cleaner code:

```php
// In ModelQueryBuilder hydration:
return array_map(fn (array $result) => $this->hydrate($result), $results);

// In MigrationExecutor:
$method = $direction === 'up' ? $this->runUp(...) : $this->runDown(...);
```

**4. Match Expressions (PHP 8.0)**

Type casting uses match for exhaustive type handling:

```php
protected function castAttribute(mixed $value, string $type): mixed
{
    return match (true) {
        $type === 'datetime' => $value instanceof \DateTimeImmutable
            ? $value
            : new \DateTimeImmutable($value),
        is_subclass_of($type, \BackedEnum::class) => $type::from($value),
        is_subclass_of($type, Contracts\Castable::class) => $type::from($value),
        default => $value,
    };
}
```

**5. Readonly Classes and Properties (PHP 8.1/8.2)**

```php
final readonly class MigrationExecutor { /* ... */ }

readonly class RelationFactory { /* ... */ }

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class HasMany { /* ... */ }
```

### Active Record Pattern Implementation

The ORM implements the classic **Active Record** pattern where each model instance represents a database row and contains both data and behavior:

**Key Design Decisions:**

1. **Automatic State Detection**: Models don't need explicit "new" or "persisted" flags. The `PropertyObserver` determines state by checking if the primary key has been set:
   ```php
   public bool $is_new {
       get => ! isset($this->changedProperties[$this->model->primary_key_name]);
   }
   ```

2. **Dirty Tracking**: Only changed properties are included in UPDATE queries, reducing database load:
   ```php
   // User changes only email
   $user->email = 'new@example.com'; // Tracked
   $user->save(); // UPDATE users SET email = 'new@example.com' WHERE id = 1
   // Name, created_at, etc. are NOT included in query
   ```

3. **Single Responsibility**: Each persistence operation is handled by a dedicated class:
   - `EntityInserter` - INSERT logic
   - `EntityUpdater` - UPDATE logic
   - `EntityManager` - Coordination (delegates to inserter/updater)

4. **Convention over Configuration**: Table names are auto-pluralized from class names:
   ```php
   User::class → 'users' table
   Post::class → 'posts' table
   Category::class → 'categories' table
   ```

### Relationship Loading Strategy

Relationships use **lazy loading** with caching:

```php
// First access triggers query
$user->posts; // Executes: SELECT * FROM posts WHERE user_id = 1
              // Caches result in RelationDecorator

// Subsequent access uses cache
$user->posts; // No query, returns cached result
```

The `RelationDecorator` manages lazy loading:

```php
public function __get(string $name): mixed
{
    if (isset($this->loadedRelations[$name])) {
        return $this->loadedRelations[$name]; // Return cached
    }

    // Load and cache relationship
    $relation = $this->factory->createFromAttribute($this->model, $name);
    $this->loadedRelations[$name] = $relation->getRelated();
    return $this->loadedRelations[$name];
}
```

### Anti-Pattern: No Direct JSON Serialization

The ORM deliberately **prevents** direct model serialization:

```php
public function jsonSerialize(): array
{
    throw new LogicException(
        'Direct serialization of models is not allowed. Use a Data Transfer Object (DTO) for serialization'
    );
}
```

**Rationale:**
- **Separation of Concerns**: Models represent database entities with behavior; DTOs define API contracts
- **Schema Independence**: Database structure can evolve without breaking API responses
- **Security**: Prevents accidental exposure of sensitive fields (passwords, tokens, etc.)
- **Flexibility**: DTOs can aggregate data from multiple models, transform values, etc.

**Correct Approach:**
```php
// Wrong:
return json_encode($user); // Throws LogicException

// Correct:
$userDTO = new UserDTO(
    id: $user->id,
    name: $user->name,
    email: $user->email,
    // Explicitly choose which fields to expose
);
return json_encode($userDTO);
```

## Comparison with Other Frameworks

> **Larafony's ORM approach takes the best from Laravel Eloquent, Symfony Doctrine, and C# Entity Framework, and empowers them with the full power of PHP 8.5.**

| Feature | Larafony | Laravel Eloquent | Symfony Doctrine | Entity Framework Core (C#) |
|---------|----------|------------------|------------------|----------------------------|
| **Pattern** | Active Record | Active Record | Data Mapper | Data Mapper |
| **Configuration** | PHP Attributes | Methods/Properties | PHP Attributes/Annotations | Attributes/Fluent API |
| **Relationships** | `#[HasMany]`, `#[BelongsTo]`, etc. | Methods (`hasMany()`, `belongsTo()`) | `#[OneToMany]`, `#[ManyToOne]`, etc. | Navigation Properties + `[ForeignKey]` |
| **Property Tracking** | `PropertyObserver` with dirty checking | Dirty tracking via `$attributes` array | Unit of Work pattern | Change Tracker with state entries |
| **Query Builder** | `ModelQueryBuilder` wraps base QB | Eloquent Builder extends base | DQL (Doctrine Query Language) | LINQ to Entities |
| **State Detection** | Automatic via primary key check | `exists` property | Managed by `EntityManager` | Tracked by `DbContext` |
| **Lazy Loading** | Attribute-based with `RelationDecorator` | Dynamic properties | Proxy objects | Navigation properties |
| **Table Naming** | Convention-based pluralization | Convention-based pluralization | Must specify via attributes | Convention-based pluralization |
| **JSON Serialization** | Forbidden (enforces DTOs) | Allowed (with `$hidden`/`$visible`) | Allowed via serializer | Allowed (with `[JsonIgnore]`) |
| **Type Casting** | `$casts` array with match expression | `$casts` array | Type hints + custom types | Automatic via property types |
| **Pivot Table** | `attach()`, `detach()`, `sync()` | `attach()`, `detach()`, `sync()` | Separate entity with `#[JoinTable]` | Automatic join table or explicit entity |
| **PHP/C# Features** | Asymmetric visibility, property hooks | Traditional getters/setters | Traditional properties | C# properties with `get`/`set` |

**What Larafony Takes from Each Framework:**

1. **From Laravel Eloquent**:
   - Active Record pattern for intuitive usage (`$model->save()`)
   - Fluent query builder with method chaining
   - `attach()`/`detach()`/`sync()` methods for many-to-many pivot management

2. **From Symfony Doctrine**:
   - Attribute-based configuration (modern, type-safe, self-documenting)
   - Clear separation of concerns (EntityManager pattern for persistence coordination)
   - Professional architecture suitable for enterprise applications

3. **From C# Entity Framework**:
   - Navigation properties (access relationships as properties, not method calls)
   - Strict typing throughout the system
   - Change tracking architecture with state management

4. **PHP 8.5 Superpowers**:
   - Asymmetric visibility (`public protected(set)`) - cleaner encapsulation than getters/setters
   - Property hooks - automatic dirty tracking without magic methods
   - Readonly classes - immutable attribute definitions
   - Match expressions - exhaustive type casting
   - First-class callables - cleaner functional code

**Key Differences:**

- **Larafony's Attribute-First Approach**: Unlike Laravel's method-based relationships (`public function posts() { return $this->hasMany(...); }`), Larafony uses PHP 8 attributes on properties. This makes relationships self-documenting and eliminates magic methods.

- **Data Mapper vs Active Record**:
  - **Larafony & Laravel (Active Record)**: Models contain both data and persistence logic (`$user->save()`). Simple and intuitive but couples domain logic to database.
  - **Doctrine & EF Core (Data Mapper)**: Entities are POPOs (Plain Old PHP Objects) or POCOs (Plain Old C# Objects) with no persistence logic. An `EntityManager` or `DbContext` handles saving. Better separation but more boilerplate.

- **PHP 8.5 Features**: Larafony uses cutting-edge PHP features unavailable in Laravel/Doctrine:
  - **Asymmetric visibility** (`public protected(set)`) - Cleaner than private properties with public getters
  - **Property hooks** - Automatic change tracking without magic `__set()`
  - **Readonly classes** - Immutable attribute definitions

- **DTO Enforcement**: Larafony is the **only framework** that explicitly forbids direct model serialization, enforcing best practices for API design. Laravel, Doctrine, and EF Core all allow it (though Laravel provides `$hidden`/`$visible` for filtering).

- **Lazy Loading Implementation**:
  - **Laravel**: Uses `__get()` magic method, returns Collection/Model directly
  - **Doctrine**: Uses Proxy objects with Reflection API
  - **EF Core**: Uses C# expression trees and IQueryable
  - **Larafony**: Uses `RelationDecorator` with attribute reflection and caching

- **Query Language**:
  - **Laravel & Larafony**: Fluent API with method chaining (`where()->orderBy()->get()`)
  - **Doctrine**: DQL (SQL-like but object-oriented: `SELECT u FROM User u WHERE u.email = ?1`)
  - **EF Core**: LINQ (C# language-integrated queries: `users.Where(u => u.Email == email).ToList()`)

- **Pluralization**: All frameworks use intelligent pluralization for table names, but Larafony's `Pluralizer` includes extensive irregular forms (person→people, mouse→mice, analysis→analyses, etc.).

- **Many-to-Many Handling**:
  - **Larafony & Laravel**: Pivot table abstracted away with `attach()`/`detach()`/`sync()` methods
  - **Doctrine**: Pivot table can be explicit entity or managed via `#[JoinTable]`
  - **EF Core**: Automatic join table or explicit entity with fluent configuration

**Entity Framework Code Example (C#) for Comparison:**

```csharp
// Entity Framework Core (C#) - Data Mapper pattern
public class User
{
    public int Id { get; set; }
    public string Name { get; set; }
    public string Email { get; set; }

    // Navigation property for relationship
    public ICollection<Post> Posts { get; set; }
}

// Usage requires DbContext (separate persistence layer)
using var context = new AppDbContext();

var user = new User { Name = "John", Email = "john@example.com" };
context.Users.Add(user); // Tell context to track entity
context.SaveChanges(); // Persist to database

// Query with LINQ
var users = context.Users
    .Where(u => u.Email.Contains("@example.com"))
    .OrderBy(u => u.Name)
    .ToList();

// Relationships
var userWithPosts = context.Users
    .Include(u => u.Posts) // Eager load
    .FirstOrDefault(u => u.Id == 1);
```

**Comparison Summary:**

- **Larafony**: Modern PHP (attributes, hooks), Active Record, enforced DTOs, attribute-based relationships
- **Laravel**: Mature ecosystem, method-based relationships, allows model serialization
- **Doctrine**: Enterprise-grade, Data Mapper, most complex but best separation of concerns
- **EF Core**: C# language integration, LINQ queries, powerful but different language ecosystem

Larafony strikes a balance: Active Record's simplicity with modern PHP features and strict architectural boundaries (DTO enforcement).

---

📚 **Learn More:** This implementation is explained in detail with step-by-step tutorials, tests, and best practices at [masterphp.eu](https://masterphp.eu)
