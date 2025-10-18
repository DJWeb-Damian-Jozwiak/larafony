# Chapter 13: ORM - ActiveRecord with Property Observers

## Overview

In this chapter, I implemented a complete **ORM (Object-Relational Mapping)** system using the **ActiveRecord pattern** with automatic **property change tracking**. The implementation combines Laravel's intuitive API with modern PHP 8.4+ features like asymmetric property visibility and property observers inspired by C# Entity Framework Core.

## Quick Start

```php
// Create a model with migration
php bin/console.php make:model Post --migration

// Define your model
class Post extends Model
{
    public string $table {
        get => 'posts';
    }

    public ?string $title {
        get => $this->title;
        set {
            $this->title = $value;
            $this->markPropertyAsChanged('title');
        }
    }

    public ?string $content {
        get => $this->content;
        set {
            $this->content = $value;
            $this->markPropertyAsChanged('content');
        }
    }
}

// Use the model
$post = new Post();
$post->title = 'Hello World';
$post->content = 'My first post';
$post->save(); // INSERT

$post->title = 'Updated Title';
$post->save(); // UPDATE only 'title' column!

// Query
$posts = Post::query()
    ->where('status', '=', 'published')
    ->orderBy('created_at', 'DESC')
    ->get();

// Find by ID
$post = Post::query()->where('id', '=', 1)->first();
```

## Core Philosophy: Property Observers

The key innovation in Larafony's ORM is **automatic property change tracking** through property observers - a pattern inspired by C# Entity Framework Core:

```php
public ?string $title {
    get => $this->title;
    set {
        $this->title = $value;
        $this->markPropertyAsChanged('title'); // Track changes automatically
    }
}
```

**Why this matters:**
- ✅ Only changed properties are updated in database
- ✅ Efficient `UPDATE` queries (no full-row updates)
- ✅ Automatic change tracking without magic
- ✅ Type-safe with full IDE support

## The `make:model` Command

```bash
# Create model only
php bin/console.php make:model User

# Create model + migration
php bin/console.php make:model Post --migration
```

### Generated Model

```php
<?php

namespace App\Models;

use Larafony\Framework\Database\ORM\Model;

class Post extends Model
{
    protected string $table {
        get => 'posts';
    }
}
```

### Intelligent Table Naming

The command uses a comprehensive pluralizer with 40+ irregular forms:

```
User        → users
Post        → posts
Category    → categories
Person      → people         // Irregular
Child       → children       // Irregular
BlogPost    → blog_posts     // snake_case + pluralize
```

## Model Definition

Every model extends the base `Model` class and defines its table name:

```php
use Larafony\Framework\Database\ORM\Model;

class User extends Model
{
    // Required: table name
    public string $table {
        get => 'users';
    }

    // Properties with change tracking
    public ?string $name {
        get => $this->name;
        set {
            $this->name = $value;
            $this->markPropertyAsChanged('name');
        }
    }

    public ?string $email {
        get => $this->email;
        set {
            $this->email = $value;
            $this->markPropertyAsChanged('email');
        }
    }

    // Type casting
    protected array $casts = [
        'created_at' => 'datetime',
        'is_active' => 'bool',
    ];
}
```

### Key Concepts

**1. Table Property (Required)**
```php
public string $table {
    get => 'users';
}
```

**2. Property Observers (Manual)**
```php
public ?string $name {
    get => $this->name;
    set {
        $this->name = $value;
        $this->markPropertyAsChanged('name'); // Track this change
    }
}
```

**3. Type Casting**
```php
protected array $casts = [
    'status' => UserStatus::class,      // Enum
    'created_at' => 'datetime',          // DateTimeImmutable
    'metadata' => CustomCast::class,     // Custom Castable
];
```

## Property Change Tracking

The `PropertyObserver` automatically tracks which properties have changed:

```php
$user = new User();
$user->id = 1; // Marks as not new
$user->name = 'John';
$user->email = 'john@example.com';

// Observer tracks: ['name' => 'John', 'email' => 'john@example.com']

$user->save();
// INSERT INTO users (name, email) VALUES ('John', 'john@example.com')

$user->name = 'Jane';
// Observer tracks: ['name' => 'Jane']

$user->save();
// UPDATE users SET name = 'Jane' WHERE id = 1
// ⚠️ Only updates 'name', not 'email'!
```

## Built-in Model Properties

Every model has these properties automatically:

```php
// Primary key (default: 'id')
$model->primary_key_name = 'id';

// Check if model is new or persisted
$model->is_new; // true if no ID set

// Access query builder
$model->query_builder;

// Access property observer
$model->observer;
```

## CRUD Operations

### Create

```php
// Method 1: New instance + save
$user = new User();
$user->name = 'John Doe';
$user->email = 'john@example.com';
$user->save();

// Method 2: Fill from array + save
$user = new User();
$user->fill([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);
$user->save();
```

### Read

```php
// Get all
$users = User::query()->get();

// Find first
$user = User::query()
    ->where('email', '=', 'john@example.com')
    ->first();

// Complex queries
$users = User::query()
    ->where('status', '=', 'active')
    ->where('role', '=', 'admin')
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->get();

// Count
$count = User::query()
    ->where('status', '=', 'active')
    ->count();
```

### Update

```php
// Find and update
$user = User::query()->where('id', '=', 1)->first();
$user->name = 'Jane Doe';
$user->save(); // Only updates 'name' column!

// Mass fill + update
$user->fill(['name' => 'Jane', 'email' => 'jane@example.com']);
$user->save();
```

### Delete

```php
// Delete instance
$user = User::query()->where('id', '=', 1)->first();
$user->delete();
```

## Relationships

Larafony supports relationships through **attributes** on model properties:

### One-to-Many (HasMany)

```php
use Larafony\Framework\Database\ORM\Attributes\HasMany;
use Larafony\Framework\Database\ORM\Relations\HasMany as HasManyRelation;

class User extends Model
{
    #[HasMany(related: Post::class, foreignKey: 'user_id', localKey: 'id')]
    public ?HasManyRelation $posts {
        get => $this->relations->get('posts');
    }
}

// Usage
$user = User::query()->where('id', '=', 1)->first();
$posts = $user->posts->get(); // Array of Post models
```

### Belongs To

```php
use Larafony\Framework\Database\ORM\Attributes\BelongsTo;
use Larafony\Framework\Database\ORM\Relations\BelongsTo as BelongsToRelation;

class Post extends Model
{
    #[BelongsTo(related: User::class, foreignKey: 'user_id', ownerKey: 'id')]
    public ?BelongsToRelation $user {
        get => $this->relations->get('user');
    }

    public ?int $user_id {
        get => $this->user_id;
        set {
            $this->user_id = $value;
            $this->markPropertyAsChanged('user_id');
        }
    }
}

// Usage
$post = Post::query()->where('id', '=', 1)->first();
$author = $post->user->first(); // User model
```

### Many-to-Many (BelongsToMany)

```php
use Larafony\Framework\Database\ORM\Attributes\BelongsToMany;
use Larafony\Framework\Database\ORM\Relations\BelongsToMany as BelongsToManyRelation;

class Post extends Model
{
    #[BelongsToMany(
        related: Tag::class,
        table: 'post_tag',
        foreignPivotKey: 'post_id',
        relatedPivotKey: 'tag_id',
        parentKey: 'id',
        relatedKey: 'id'
    )]
    public ?BelongsToManyRelation $tags {
        get => $this->relations->get('tags');
    }
}

class Tag extends Model
{
    #[BelongsToMany(
        related: Post::class,
        table: 'post_tag',
        foreignPivotKey: 'tag_id',
        relatedPivotKey: 'post_id',
        parentKey: 'id',
        relatedKey: 'id'
    )]
    public ?BelongsToManyRelation $posts {
        get => $this->relations->get('posts');
    }
}

// Usage
$post = Post::query()->where('id', '=', 1)->first();
$tags = $post->tags->get(); // Array of Tag models

// Attach tags
$post->tags->attach([1, 2, 3]);

// Detach
$post->tags->detach([2]);

// Sync (replace all)
$post->tags->sync([1, 3, 4]);
```

### Has-Many-Through

```php
use Larafony\Framework\Database\ORM\Attributes\HasManyThrough;
use Larafony\Framework\Database\ORM\Relations\HasManyThrough as HasManyThroughRelation;

class Country extends Model
{
    #[HasManyThrough(
        related: Post::class,
        through: User::class,
        firstKey: 'country_id',
        secondKey: 'user_id',
        localKey: 'id',
        secondLocalKey: 'id'
    )]
    public ?HasManyThroughRelation $posts {
        get => $this->relations->get('posts');
    }
}

// Get all posts from a country through users
$country = Country::query()->where('id', '=', 1)->first();
$posts = $country->posts->get();
```

## Type Casting

The `$casts` array automatically converts database values:

```php
class User extends Model
{
    protected array $casts = [
        'created_at' => 'datetime',      // string → DateTimeImmutable
        'status' => UserStatus::class,   // string → BackedEnum
        'metadata' => JsonCast::class,   // string → Castable
    ];
}

// Usage
$user = new User();
$user->fill(['created_at' => '2024-01-01 12:00:00']);

var_dump($user->created_at); // DateTimeImmutable object
```

### Supported Cast Types

1. **DateTime**: `'datetime'` → `DateTimeImmutable`
2. **Enums**: `UserStatus::class` → `BackedEnum::from($value)`
3. **Custom Castables**: Implement `Castable` contract

```php
use Larafony\Framework\Database\ORM\Contracts\Castable;

class JsonCast implements Castable
{
    public static function from(mixed $value): array
    {
        return json_decode($value, true);
    }

    public static function to(mixed $value): string
    {
        return json_encode($value);
    }
}
```

## The `fill()` Method

Mass assign properties from an array:

```php
$user = new User();
$user->fill([
    'name' => 'John',
    'email' => 'john@example.com',
    'created_at' => '2024-01-01',
]);

// Equivalent to:
$user->name = 'John';
$user->email = 'john@example.com';
$user->created_at = '2024-01-01'; // Auto-cast to DateTimeImmutable
```

**Features:**
- ✅ Only sets properties that exist on the model
- ✅ Ignores non-existent keys
- ✅ Applies type casts from `$casts` array
- ✅ Triggers property observers (change tracking)

## No Direct JSON Serialization

Models **cannot** be serialized to JSON directly - this is by design:

```php
$user = User::query()->where('id', '=', 1)->first();
json_encode($user); // ❌ Throws LogicException!
```

**Why?**
- Enforces separation of concerns
- Database entities ≠ API responses
- Use DTOs (Data Transfer Objects) for serialization

```php
// ✅ Correct: Use DTO
class UserDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email
        );
    }
}

$user = User::query()->where('id', '=', 1)->first();
$dto = UserDTO::fromModel($user);
json_encode($dto); // ✅ Works!
```

## Comparison with Other Frameworks

| Feature | Larafony | Laravel | Symfony (Doctrine) | C# EF Core |
|---------|----------|---------|-------------------|------------|
| **Pattern** | ActiveRecord | ActiveRecord | Data Mapper | ActiveRecord-like |
| **Property tracking** | Property observers (explicit) | Magic `$dirty` array | UnitOfWork automatic | ChangeTracker automatic |
| **Property visibility** | Asymmetric (`public ?string $name { get; set; }`) | Magic `__get`/`__set` | Public properties | `public string Name { get; private set; }` |
| **Type safety** | ✅ Native PHP types | ⚠️ Mixed arrays + docblocks | ⚠️ Annotations/Attributes | ✅ Full type safety |
| **Change detection** | `markPropertyAsChanged()` | Automatic via magic | Automatic via proxy | Automatic |
| **Relationships** | Attributes on properties | Methods returning relations | Attributes on properties | Data Annotations or Fluent API |
| **Save entity** | `$post->save()` | `$post->save()` | `$em->persist(); $em->flush()` | `context.SaveChanges()` |
| **Query API** | `Post::query()->where(...)` | `Post::where(...)` | `$repo->createQueryBuilder()` | `context.Posts.Where()` |
| **Repository** | Built into model (`::query()`) | Built into model | Separate repository classes | DbContext |
| **JSON serialization** | ❌ Forbidden (use DTOs) | ✅ Automatic `toArray()` | ✅ Serializer groups | ✅ Automatic |
| **Learning curve** | Low | Low | High | Medium |
| **Boilerplate** | Minimal | Minimal | Heavy (getters/setters) | Medium |
| **Performance** | Direct property access | Magic method overhead | Proxy overhead | Compiled expressions |

### Code Examples

**Creating and saving an entity:**

```php
// Larafony - Explicit, type-safe
$post = new Post();
$post->title = 'Hello World';
$post->save();
```

```php
// Laravel - Magic methods
$post = new Post();
$post->title = 'Hello World';
$post->save();
```

```php
// Symfony Doctrine - Verbose
$post = new Post();
$post->setTitle('Hello World');
$entityManager->persist($post);
$entityManager->flush();
```

```csharp
// C# EF Core - Clean
var post = new Post { Title = "Hello World" };
context.Posts.Add(post);
context.SaveChanges();
```

**Querying with filters:**

```php
// Larafony
$posts = Post::query()
    ->where('status', '=', 'published')
    ->orderBy('created_at', 'DESC')
    ->get();
```

```php
// Laravel
$posts = Post::where('status', 'published')
    ->orderBy('created_at', 'DESC')
    ->get();
```

```php
// Symfony Doctrine
$posts = $entityManager->getRepository(Post::class)
    ->createQueryBuilder('p')
    ->where('p.status = :status')
    ->setParameter('status', 'published')
    ->orderBy('p.created_at', 'DESC')
    ->getQuery()
    ->getResult();
```

```csharp
// C# EF Core
var posts = context.Posts
    .Where(p => p.Status == "published")
    .OrderByDescending(p => p.CreatedAt)
    .ToList();
```

**Property definition:**

```php
// Larafony - Property observers
class Post extends Model
{
    public ?string $title {
        get => $this->title;
        set {
            $this->title = $value;
            $this->markPropertyAsChanged('title');
        }
    }
}
```

```php
// Laravel - Magic
class Post extends Model
{
    protected $fillable = ['title'];
    // Uses __get() and __set() magic methods
}
```

```php
// Symfony Doctrine - Traditional OOP
#[Entity]
class Post
{
    #[Column(type: 'string')]
    private string $title;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
}
```

```csharp
// C# EF Core - Auto-properties
public class Post
{
    public string Title { get; set; }
    // Change tracking handled by EF Core
}
```

## The Command Facade

Call console commands programmatically:

```php
use Larafony\Framework\Core\Helpers\CommandCaller;

$caller = new CommandCaller($container, $registry);

// Create model
$caller->call('make:model', ['name' => 'Category']);

// Create model with migration
$caller->call('make:model', ['name' => 'Product'], ['--migration' => true]);

// Run migrations
$caller->call('migrate');
```

## Architecture

```
ORM System
├── Model (base class)
│   ├── PropertyObserver          - Tracks property changes
│   ├── EntityManager            - Handles save/delete
│   │   ├── EntityInserter       - INSERT operations
│   │   └── EntityUpdater        - UPDATE operations
│   ├── ModelQueryBuilder        - Fluent query API
│   └── RelationDecorator        - Manages relationships
│
├── Relations
│   ├── HasMany
│   ├── BelongsTo
│   ├── BelongsToMany
│   └── HasManyThrough
│
└── Attributes (for relationships)
    ├── #[HasMany]
    ├── #[BelongsTo]
    ├── #[BelongsToMany]
    └── #[HasManyThrough]
```

## File Structure

```
framework/
├── src/Larafony/
│   ├── Database/ORM/
│   │   ├── Model.php                     # Base model
│   │   ├── PropertyObserver.php          # Change tracking
│   │   ├── Decorators/
│   │   │   ├── EntityManager.php         # Save/delete coordinator
│   │   │   ├── EntityInserter.php        # INSERT logic
│   │   │   └── EntityUpdater.php         # UPDATE logic
│   │   ├── QueryBuilders/
│   │   │   └── ModelQueryBuilder.php     # Fluent queries
│   │   ├── Relations/
│   │   │   ├── HasMany.php
│   │   │   ├── BelongsTo.php
│   │   │   ├── BelongsToMany.php
│   │   │   ├── HasManyThrough.php
│   │   │   ├── RelationDecorator.php
│   │   │   └── RelationFactory.php
│   │   ├── Attributes/
│   │   │   ├── HasMany.php
│   │   │   ├── BelongsTo.php
│   │   │   ├── BelongsToMany.php
│   │   │   └── HasManyThrough.php
│   │   └── Contracts/
│   │       ├── PropertyChangesContract.php
│   │       ├── RelationContract.php
│   │       └── Castable.php
│   │
│   ├── Console/Commands/
│   │   └── MakeModel.php
│   │
│   └── Core/
│       ├── Support/
│       │   ├── Pluralizer.php
│       │   └── StrHelpers/
│       │       ├── RegularPluralize.php
│       │       └── PreserveCase.php
│       └── Helpers/
│           └── CommandCaller.php
│
└── tests/Larafony/Database/ORM/
    ├── ModelTest.php
    ├── PropertyObserverTest.php
    ├── Decorators/
    │   ├── EntityManagerTest.php
    │   ├── EntityInserterTest.php
    │   └── EntityUpdaterTest.php
    └── Relations/
        ├── HasManyTest.php
        ├── BelongsToTest.php
        ├── BelongsToManyTest.php
        └── HasManyThroughTest.php
```

## Key Takeaways

✅ **ActiveRecord Pattern** - Models are your database tables
✅ **Property Observers** - Explicit change tracking with `markPropertyAsChanged()`
✅ **Asymmetric Properties** - PHP 8.4's `public ?string $name { get; set; }`
✅ **Type Safety** - Native PHP types, not magic arrays
✅ **Attribute-Based Relations** - `#[HasMany]`, `#[BelongsTo]`, etc.
✅ **No Magic JSON** - Enforces DTOs for serialization
✅ **Intelligent Pluralization** - 40+ irregular forms
✅ **Efficient Updates** - Only changed columns are updated
✅ **Laravel-like API** - Familiar to Laravel developers
✅ **Simpler than Doctrine** - Less boilerplate, faster development

**The Result:** A modern, type-safe ORM that combines ActiveRecord simplicity with C#-inspired property observers, powered by PHP 8.5+! 🚀
