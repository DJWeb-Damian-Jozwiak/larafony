<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\Entities;

use Larafony\Framework\Database\ORM\Attributes\BelongsToMany;
use Larafony\Framework\Database\ORM\Model;

class Role extends Model
{
    public string $name {
        get => $this->name;
        set {
            $this->name = $value;
            $this->markPropertyAsChanged('name');
        }
    }

    public ?string $description {
        get => $this->description;
        set {
            $this->description = $value;
            $this->markPropertyAsChanged('description');
        }
    }

    /**
     * @var array<Permission> $permissions
     */
    #[BelongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id')]
    public array $permissions {
        get => $this->relations->getRelation('permissions');
    }

    /**
     * @var array<int, User> $users
     */
    #[BelongsToMany(User::class, 'user_roles', 'role_id', 'user_id')]
    public array $users {
        get => $this->relations->getRelation('users');
    }

    public function hasPermission(string $permissionName): bool
    {
        return in_array($permissionName, array_column($this->permissions, 'name'));
    }
}
