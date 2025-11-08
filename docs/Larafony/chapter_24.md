# Chapter 24: Authorization - Roles and Permissions

> Part of the Larafony Framework - A modern PHP 8.5 framework built from scratch
>
> 📚 Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)

## Overview

Chapter 24 implements a complete Role-Based Access Control (RBAC) system for Larafony, providing fine-grained authorization capabilities through roles and permissions. The implementation is built entirely from scratch without external dependencies, leveraging the framework's existing ORM with many-to-many relationships.

The authorization system follows a hierarchical model where **Users** have **Roles**, and **Roles** have **Permissions**. This allows flexible permission management where a single role can grant multiple permissions, and users can have multiple roles. The system provides both direct permission checks and role-based checks through a clean, expressive API similar to Laravel's gate system but with a more explicit permission model.

Unlike Laravel's built-in authorization which relies on external packages like Spatie for RBAC, Larafony includes roles and permissions as core framework features. The implementation uses PHP 8.5's property hooks for clean data access, the framework's ORM with `BelongsToMany` relationships for efficient data loading, and a facade-style `Auth` class for convenient access throughout the application.

## Key Components

### Authorization Managers

- **RoleManager** - Handles role checks for authenticated users (with helper methods: `hasRole()`, `hasAnyRole()`, `hasAllRoles()`)
- **PermissionManager** - Handles permission checks for authenticated users (with helper methods: `hasPermission()`, `hasAnyPermission()`, `hasAllPermissions()`)
- **Auth** - Static facade providing unified access to authentication and authorization features

### ORM Entities

- **Role** - Represents a role with many-to-many relationships to both users and permissions
- **Permission** - Represents a granular permission with many-to-many relationship to roles
- **User** - Extended with role and permission checking capabilities

### Database Setup Commands

The framework includes console commands to set up the authorization tables:
- `table:auth-role` - Creates the `roles` table
- `table:auth-permission` - Creates the `permissions` table
- `table:auth-role-permission` - Creates the pivot table linking roles to permissions
- `table:auth-user-role` - Creates the pivot table linking users to roles

## Database Structure

The authorization system uses four main tables:

**roles**
```sql
- id (primary key)
- name (unique, string, 100 chars)
- description (nullable, string)
- created_at, updated_at (timestamps)
```

**permissions**
```sql
- id (primary key)
- name (unique, string, 100 chars)
- description (nullable, string)
- created_at, updated_at (timestamps)
```

**role_permissions** (pivot)
```sql
- id (primary key)
- role_id (foreign key to roles, indexed)
- permission_id (foreign key to permissions, indexed)
- created_at (timestamp)
- unique constraint on (role_id, permission_id)
```

**user_roles** (pivot)
```sql
- id (primary key)
- user_id (foreign key to users, indexed)
- role_id (foreign key to roles, indexed)
- created_at (timestamp)
- unique constraint on (user_id, role_id)
```

## Usage Examples

### Setting Up Roles and Permissions

```php
<?php

use Larafony\Framework\Database\ORM\Entities\Role;
use Larafony\Framework\Database\ORM\Entities\Permission;
use Larafony\Framework\Database\ORM\Entities\User;

// Create permissions
$createNotes = new Permission();
$createNotes->name = 'notes.create';
$createNotes->description = 'Can create notes';
$createNotes->save();

$editNotes = new Permission();
$editNotes->name = 'notes.edit';
$editNotes->description = 'Can edit notes';
$editNotes->save();

$deleteNotes = new Permission();
$deleteNotes->name = 'notes.delete';
$deleteNotes->description = 'Can delete notes';
$deleteNotes->save();

// Create roles
$adminRole = new Role();
$adminRole->name = 'admin';
$adminRole->description = 'Administrator with full access';
$adminRole->save();

$editorRole = new Role();
$editorRole->name = 'editor';
$editorRole->description = 'Can create and edit content';
$editorRole->save();

// Attach permissions to roles using BelongsToMany relation
$adminRole->relations->getRelationInstance('permissions')
    ->attach([$createNotes->id, $editNotes->id, $deleteNotes->id]);

$editorRole->relations->getRelationInstance('permissions')
    ->attach([$createNotes->id, $editNotes->id]);

// Assign role to user
$user = User::query()->where('email', '=', 'john@example.com')->first();
$user->addRole($adminRole);
```

### Checking Permissions in Controllers

```php
<?php

use Larafony\Framework\Auth\Auth;
use Larafony\Framework\Web\Controller;
use Psr\Http\Message\ResponseInterface;

class NoteController extends Controller
{
    public function create(): ResponseInterface
    {
        // Check if user has specific permission
        if (!Auth::hasPermission('notes.create')) {
            return $this->json([
                'message' => 'Forbidden',
                'errors' => ['permission' => ['You do not have permission to create notes.']]
            ], 403);
        }

        // User has permission, proceed with creation
        return $this->render('notes.create');
    }

    public function delete(int $id): ResponseInterface
    {
        // Check if user has any of the specified permissions
        if (!Auth::hasAnyPermission(['notes.delete', 'admin.all'])) {
            return $this->json(['message' => 'Forbidden'], 403);
        }

        // Proceed with deletion
        Note::query()->where('id', '=', $id)->delete();

        return $this->json(['message' => 'Note deleted successfully']);
    }
}
```

### Checking Roles

```php
<?php

use Larafony\Framework\Auth\Auth;

// Check if user has a specific role
if (Auth::hasRole('admin')) {
    // User is an admin
}

// Check if user has any of the specified roles
if (Auth::hasAnyRole(['admin', 'moderator'])) {
    // User is either admin or moderator
}

// Check if user has all specified roles
if (Auth::hasAllRoles(['admin', 'super-user'])) {
    // User has both admin AND super-user roles
}
```

### Direct Model Usage

```php
<?php

use Larafony\Framework\Auth\Auth;

$user = Auth::user();

// Check roles directly on user model
if ($user->hasRole('admin')) {
    // User has admin role
}

// Check permissions directly on user model
if ($user->hasPermission('notes.delete')) {
    // User has permission through their roles
}

// Access relationships using PHP 8.5 property hooks
foreach ($user->roles as $role) {
    echo $role->name;

    // Check if role has specific permission
    if ($role->hasPermission('notes.create')) {
        echo "This role can create notes";
    }
}
```

## Implementation Details

### RoleManager

**Location:** `src/Larafony/Auth/RoleManager.php`

**Purpose:** Manages role-based authorization checks for authenticated users, delegating to the UserManager for authentication state and the User model for actual role verification.

**Key Methods:**
- `hasRole(string $role): bool` - Checks if authenticated user has a specific role by name
- `hasAnyRole(array $roles): bool` - Checks if authenticated user has at least one of the specified roles
- `hasAllRoles(array $roles): bool` - Checks if authenticated user has all specified roles

**Dependencies:**
- `UserManager` - For retrieving the currently authenticated user

**Usage:**
```php
$roleManager = new RoleManager($userManager);

if ($roleManager->hasRole('admin')) {
    // User is an admin
}

if ($roleManager->hasAnyRole(['editor', 'moderator'])) {
    // User has at least one of these roles
}
```

**Implementation Notes:**
- Always checks if user is authenticated before checking roles
- Returns `false` immediately if user is not authenticated
- Uses PHP 8.5's `array_any()` and `array_all()` functions for elegant collection operations
- Readonly class for immutability

### PermissionManager

**Location:** `src/Larafony/Auth/PermissionManager.php`

**Purpose:** Manages permission-based authorization checks for authenticated users. Permissions are checked through the user's roles, supporting indirect permission grants.

**Key Methods:**
- `hasPermission(string $permission): bool` - Checks if authenticated user has a specific permission through any of their roles
- `hasAnyPermission(array $permissions): bool` - Checks if authenticated user has at least one of the specified permissions
- `hasAllPermissions(array $permissions): bool` - Checks if authenticated user has all specified permissions

**Dependencies:**
- `UserManager` - For retrieving the currently authenticated user

**Usage:**
```php
$permissionManager = new PermissionManager($userManager);

if ($permissionManager->hasPermission('notes.create')) {
    // User can create notes
}

if ($permissionManager->hasAllPermissions(['notes.edit', 'notes.delete'])) {
    // User has both edit and delete permissions
}
```

**Implementation Notes:**
- Checks permissions through user's roles (not direct user-permission assignment)
- Returns `false` if user is not authenticated
- Readonly class for security and immutability

### Auth

**Location:** `src/Larafony/Auth/Auth.php`

**Purpose:** Static facade providing unified access to authentication and authorization features. Acts as the primary entry point for all auth-related operations throughout the application.

**Key Methods:**

*Authentication:*
- `attempt(User $user, string $password, bool $remember = false): bool` - Attempts to authenticate a user
- `login(User $user, bool $remember = false): void` - Logs in a user manually
- `logout(): void` - Logs out the current user
- `user(): ?User` - Returns the currently authenticated user
- `check(): bool` - Checks if a user is authenticated
- `guest(): bool` - Checks if no user is authenticated
- `id(): int|string|null` - Returns the ID of the authenticated user

*Role Authorization:*
- `hasRole(string $role): bool` - Checks if user has a specific role
- `hasAnyRole(array $roles): bool` - Checks if user has any of the specified roles
- `hasAllRoles(array $roles): bool` - Checks if user has all specified roles

*Permission Authorization:*
- `hasPermission(string $permission): bool` - Checks if user has a specific permission
- `hasAnyPermission(array $permissions): bool` - Checks if user has any of the specified permissions
- `hasAllPermissions(array $permissions): bool` - Checks if user has all specified permissions

**Dependencies:**
- `ContainerContract` - PSR-11 container for resolving UserManager
- Creates `RoleManager` and `PermissionManager` instances on-demand

**Usage:**
```php
use Larafony\Framework\Auth\Auth;

// Authentication
if (Auth::check()) {
    $user = Auth::user();
}

// Role checks
if (Auth::hasRole('admin')) {
    // Admin-only code
}

// Permission checks
if (Auth::hasPermission('posts.publish')) {
    // Publish the post
}

// Multiple permission checks
if (Auth::hasAllPermissions(['posts.create', 'posts.publish'])) {
    // Create and immediately publish
}
```

**Implementation Notes:**
- Static facade pattern for convenient access throughout the application
- Lazy initialization of managers (created only when needed)
- Container must be set via `Auth::withContainer()` during application bootstrap
- Thread-safe through static properties
- All authorization methods delegate to specialized managers

### User

**Location:** `src/Larafony/Database/ORM/Entities/User.php`

**Purpose:** Extended base User entity with role and permission management capabilities. Provides direct access to user's roles and methods to check role and permission assignments.

**Key Properties:**
- `array $roles` - BelongsToMany relationship to Role entities (lazy-loaded via property hooks)

**Key Methods:**
- `addRole(Role $role): void` - Assigns a role to the user (prevents duplicates)
- `hasRole(string $roleName): bool` - Checks if user has a specific role
- `hasPermission(string $permissionName): bool` - Checks if user has a permission through any of their roles

**Relationships:**
```php
#[BelongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')]
public array $roles { get => $this->relations->getRelation('roles'); }
```

**Usage:**
```php
$user = User::query()->where('email', '=', 'admin@example.com')->first();

// Add role to user
$adminRole = Role::query()->where('name', '=', 'admin')->first();
$user->addRole($adminRole);

// Check roles
if ($user->hasRole('admin')) {
    echo "User is an admin";
}

// Check permissions (checks through all user's roles)
if ($user->hasPermission('notes.delete')) {
    echo "User can delete notes";
}

// Access all roles
foreach ($user->roles as $role) {
    echo $role->name . "\n";
}
```

**Implementation Notes:**
- Uses PHP 8.5 property hooks for clean relationship access
- `addRole()` checks for duplicates before attaching
- Permission checks iterate through all user's roles
- Leverages ORM's BelongsToMany relation for database operations
- Inherits all authentication features from framework's base User entity

### Role

**Location:** `src/Larafony/Database/ORM/Entities/Role.php`

**Purpose:** Represents a role in the authorization system. Roles are containers for permissions and can be assigned to multiple users.

**Key Properties:**
- `string $name` - Unique role name (e.g., 'admin', 'editor')
- `?string $description` - Human-readable description of the role
- `array $permissions` - BelongsToMany relationship to Permission entities
- `array $users` - BelongsToMany relationship to User entities

**Key Methods:**
- `hasPermission(string $permissionName): bool` - Checks if this role has a specific permission

**Relationships:**
```php
#[BelongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id')]
public array $permissions { get => $this->relations->getRelation('permissions'); }

#[BelongsToMany(User::class, 'user_roles', 'role_id', 'user_id')]
public array $users { get => $this->relations->getRelation('users'); }
```

**Usage:**
```php
$role = new Role();
$role->name = 'editor';
$role->description = 'Content editor with create and edit permissions';
$role->save();

// Attach permissions
$createPermission = Permission::query()->where('name', '=', 'posts.create')->first();
$editPermission = Permission::query()->where('name', '=', 'posts.edit')->first();

$role->relations->getRelationInstance('permissions')
    ->attach([$createPermission->id, $editPermission->id]);

// Check if role has permission
if ($role->hasPermission('posts.create')) {
    echo "This role can create posts";
}

// Access all permissions for this role
foreach ($role->permissions as $permission) {
    echo $permission->name . "\n";
}

// Access all users with this role
foreach ($role->users as $user) {
    echo $user->email . "\n";
}
```

**Implementation Notes:**
- Uses PHP 8.5 property hooks with `markPropertyAsChanged()` for ORM dirty tracking
- Many-to-many relationship to both permissions and users
- `hasPermission()` uses `array_column()` for efficient name extraction and `in_array()` for checking
- Extends framework's base Model class with timestamps support

### Permission

**Location:** `src/Larafony/Database/ORM/Entities/Permission.php`

**Purpose:** Represents a granular permission in the authorization system. Permissions define specific abilities (e.g., 'posts.create', 'users.delete') and are assigned to roles.

**Key Properties:**
- `string $name` - Unique permission name (e.g., 'notes.create', 'admin.users.delete')
- `?string $description` - Human-readable description of the permission
- `array $roles` - BelongsToMany relationship to Role entities

**Relationships:**
```php
#[BelongsToMany(Role::class, 'role_permissions', 'permission_id', 'role_id')]
public array $roles { get => $this->relations->getRelation('roles'); }
```

**Usage:**
```php
$permission = new Permission();
$permission->name = 'posts.publish';
$permission->description = 'Can publish posts to make them publicly visible';
$permission->save();

// Access all roles that have this permission
foreach ($permission->roles as $role) {
    echo "Role '{$role->name}' has publish permission\n";
}

// Find permission by name
$deletePermission = Permission::query()
    ->where('name', '=', 'posts.delete')
    ->first();
```

**Implementation Notes:**
- Uses PHP 8.5 property hooks with dirty tracking
- Inverse relationship to Role (many permissions can belong to many roles)
- Permission naming convention: `resource.action` (e.g., 'posts.create', 'notes.delete')
- Extends framework's base Model class

## Comparison with Other Frameworks

| Feature | Larafony | Laravel (Spatie) | Symfony |
|---------|----------|------------------|---------|
| **Core Integration** | Built into framework core | Requires external package (spatie/laravel-permission) | Built-in voter system |
| **Database Tables** | 4 tables (roles, permissions, role_permissions, user_roles) | 5 tables (adds model_has_permissions for direct user permissions) | Configured via security.yaml, often uses role hierarchy |
| **Role Model** | `Role` entity with BelongsToMany relations | `Role` Eloquent model | No dedicated role entity, uses strings |
| **Permission Model** | `Permission` entity with BelongsToMany relations | `Permission` Eloquent model | No dedicated permission model |
| **User-Permission Assignment** | Only through roles (RBAC) | Both direct and through roles | Through voters and role hierarchy |
| **API Style** | `Auth::hasPermission('notes.create')` | `$user->hasPermissionTo('create posts')` or `$user->can('create posts')` | `$this->isGranted('ROLE_ADMIN')` or custom voters |
| **Role Checking** | `Auth::hasRole('admin')` | `$user->hasRole('admin')` | `$this->isGranted('ROLE_ADMIN')` |
| **Multiple Checks** | `hasAnyRole()`, `hasAllRoles()`, `hasAnyPermission()`, `hasAllPermissions()` | `hasAnyRole()`, `hasAllRoles()`, `hasAnyPermission()`, `hasAllPermissions()` | Custom voter logic |
| **Middleware Support** | Manual implementation in controllers | Built-in middleware (role, permission, role_or_permission) | Access control annotations/attributes |
| **Blade/View Directives** | Manual checks in views | `@role()`, `@hasrole()`, `@can()`, `@cannot()` directives | `is_granted()` Twig function |
| **PSR Compliance** | Uses PSR-11 container, ORM follows PSR standards | Laravel's service container | Fully PSR compliant (PSR-3, PSR-6, PSR-11, etc.) |
| **Configuration** | PHP code and database | Database with optional config | security.yaml configuration file |
| **Caching** | Manual if needed | Built-in permission cache | Built-in via Symfony cache |
| **Guard Support** | Single guard (session-based) | Multiple guards (web, api, etc.) | Multiple firewalls |
| **Permission Wildcards** | Not supported | Supported (e.g., 'posts.*') | Not supported (use voters) |
| **Teams/Tenancy** | Not supported | Supported via package | Custom implementation via voters |
| **Setup Commands** | `table:auth-role`, `table:auth-permission`, etc. | `php artisan permission:create-role` | N/A (config-based) |

**Key Differences:**

1. **Larafony** takes a minimalist approach with roles and permissions as first-class framework citizens, requiring no external packages. Authorization is strictly through roles (pure RBAC), which enforces a clean separation of concerns.

2. **Laravel + Spatie** provides the most flexibility with both direct user-permission assignments and role-based permissions. It includes extensive Blade directives for view-level authorization and built-in middleware for route protection. However, it requires an external package that's not part of the core framework.

3. **Symfony** uses a voter-based system that's extremely flexible and powerful for complex authorization logic. Instead of a dedicated permission model, Symfony treats roles and permissions as strings and uses voters to make authorization decisions. This is more abstract and configurable but requires more setup for RBAC patterns.

4. **Permission Naming:** Larafony uses dot notation (`notes.create`), Laravel/Spatie uses spaces or dots (`create posts` or `posts.create`), and Symfony uses `ROLE_` prefix for roles and custom strings for permissions.

5. **Architecture:** Larafony's implementation is built on its own ORM with BelongsToMany relationships and PHP 8.5 property hooks. Laravel/Spatie uses Eloquent with traditional relationships. Symfony uses configuration files and doesn't enforce a specific database structure.

## Real World Integration

This chapter's features are demonstrated in the demo application with a complete user seeding system that sets up roles, permissions, and assigns them to users.

### Demo Application Changes

The demo application includes a comprehensive seeder that demonstrates how to set up a complete authorization system with roles and permissions:

### File Structure
```
demo-app/
├── database/seeders/
│   └── UserSeeder.php          # Creates users, roles, permissions, and assignments
├── src/
│   ├── Models/
│   │   └── User.php            # Extends framework User with notes relationship
│   └── Controllers/
│       └── NoteController.php  # Demonstrates permission checks in action
```

### Implementation Example

**File: `demo-app/database/seeders/UserSeeder.php`**

```php
<?php

declare(strict_types=1);

namespace App\Database\Seeders;

use App\Models\User;
use Larafony\Framework\Database\ORM\Entities\Permission;
use Larafony\Framework\Database\ORM\Entities\Role;

class UserSeeder
{
    public function run(): void
    {
        // Create admin user
        $admin = new User();
        $admin->email = 'admin@example.com';
        $admin->username = 'admin';
        $admin->password = 'password'; // Auto-hashed with Argon2id
        $admin->is_active = 1;
        $admin->save();

        // Create regular user
        $user = new User();
        $user->email = 'user@example.com';
        $user->username = 'user';
        $user->password = 'password'; // Auto-hashed with Argon2id
        $user->is_active = 1;
        $user->save();

        // Create roles
        $adminRole = new Role();
        $adminRole->name = 'admin';
        $adminRole->description = 'Administrator role';
        $adminRole->save();

        $userRole = new Role();
        $userRole->name = 'user';
        $userRole->description = 'Regular user role';
        $userRole->save();

        // Create permissions
        // Note: Using resource.action naming convention
        $addNotePermission = new Permission();
        $addNotePermission->name = 'notes.create';
        $addNotePermission->description = 'Can create notes';
        $addNotePermission->save();

        $editNotePermission = new Permission();
        $editNotePermission->name = 'notes.edit';
        $editNotePermission->description = 'Can edit notes';
        $editNotePermission->save();

        $deleteNotePermission = new Permission();
        $deleteNotePermission->name = 'notes.delete';
        $deleteNotePermission->description = 'Can delete notes';
        $deleteNotePermission->save();

        // Assign roles to users
        // Using the addRole() method which prevents duplicate assignments
        $admin->addRole($adminRole);
        $user->addRole($userRole);
    }
}
```

**What's happening here:**

1. **User Creation**: Creates two users (admin and regular user) with Argon2id password hashing handled automatically by the User model's property hook
2. **Role Creation**: Defines two roles - 'admin' for full access and 'user' for limited access
3. **Permission Creation**: Creates three granular permissions following the `resource.action` naming convention
4. **Role Assignment**: Uses the `addRole()` method which internally uses the BelongsToMany relationship to create entries in the `user_roles` pivot table

**Note:** This seeder intentionally does NOT attach permissions to roles. In a complete implementation, you would add:

```php
// Attach all permissions to admin role
$adminRole->relations->getRelationInstance('permissions')
    ->attach([
        $addNotePermission->id,
        $editNotePermission->id,
        $deleteNotePermission->id
    ]);

// Attach limited permissions to user role
$userRole->relations->getRelationInstance('permissions')
    ->attach([
        $addNotePermission->id,
        $editNotePermission->id
    ]);
```

### Permission Checks in Controllers

**File: `demo-app/src/Controllers/NoteController.php`** (relevant excerpt)

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\DTOs\CreateNoteDto;
use Larafony\Framework\Auth\Auth;
use Larafony\Framework\Web\Controller;
use Psr\Http\Message\ResponseInterface;

class NoteController extends Controller
{
    #[Route('/notes', 'POST')]
    public function store(CreateNoteDto $dto): ResponseInterface
    {
        // Check if user is authenticated
        // Auth::check() delegates to UserManager which checks session
        if (!Auth::check()) {
            return $this->json([
                'message' => 'Unauthorized',
                'errors' => ['auth' => ['You must be logged in to create notes.']]
            ], 401);
        }

        // Check if user has permission to create notes
        // Auth::hasPermission() -> PermissionManager -> UserManager -> User -> Roles -> Permissions
        if (!Auth::hasPermission('notes.create')) {
            return $this->json([
                'message' => 'Forbidden',
                'errors' => ['permission' => ['You do not have permission to create notes.']]
            ], 403);
        }

        /** @var User $user */
        $user = Auth::user();

        // Create note with user association
        $note = new Note()->fill([
            'title' => $dto->title,
            'content' => $dto->content,
            'user_id' => $user->id, // Associate note with authenticated user
        ]);
        $note->save();

        // ... tag handling code ...

        return $this->redirect('/notes');
    }
}
```

**What's happening here:**

1. **Authentication Check**: First verifies the user is logged in using `Auth::check()` before proceeding
2. **Permission Check**: Uses `Auth::hasPermission('notes.create')` to verify authorization
3. **Permission Flow**: The check flows through: Auth facade → PermissionManager → UserManager (gets current user) → User model → user's roles → each role's permissions
4. **HTTP Status Codes**: Returns proper HTTP status codes (401 for unauthenticated, 403 for unauthorized)
5. **User Association**: Once authorized, retrieves the authenticated user and associates the new note with them

### Running the Demo

```bash
composer create-project larafony/skeleton demo-app
cd demo-app

# Build database structure and seed data (includes UserSeeder)
php bin/larafony build:notes

# Or run seeder separately
php bin/larafony app:seed

# Start development server
php -S localhost:8000 -t public
```

**Expected output:**
```
🧱 Larafony Notes Pro+ Installer
-----------------------------------

Initializing database...
✔ Database connected successfully!
✔ UserSeeder
✔ TagSeeder
✔ NoteSeeder

Installation complete 🎉
Demo user: demo@example.com
URL: http://larafony.local/notes
```

**Testing Authorization:**

1. Log in as `admin@example.com` / `password` - has admin role
2. Log in as `user@example.com` / `password` - has user role
3. Try to create a note - permission check in action
4. Without permissions attached to roles, the permission check will fail with 403 Forbidden

### Key Takeaways

- **Seeding Pattern**: The UserSeeder demonstrates a clean pattern for setting up roles and permissions in a new application
- **Role-Based Access**: Users get permissions through roles, not directly, enforcing proper RBAC architecture
- **Permission Naming**: Using dot notation (`notes.create`) creates a clear, hierarchical permission structure
- **Property Hooks**: The User model's password property hook automatically hashes passwords with Argon2id, demonstrating PHP 8.5 features in action
- **Lazy Loading**: The `addRole()` method uses the ORM's relationship manager to handle the many-to-many association automatically
- **Real-World Authorization**: The NoteController shows how to protect routes with authentication and permission checks in a production-like scenario
- **Separation of Concerns**: Authentication (`Auth::check()`) is clearly separated from authorization (`Auth::hasPermission()`)
- **HTTP Standards**: Proper use of 401 (Unauthorized - not authenticated) vs 403 (Forbidden - authenticated but not authorized)

---

📚 **Learn More:** This implementation is explained in detail with step-by-step tutorials, tests, and best practices at [masterphp.eu](https://masterphp.eu)
