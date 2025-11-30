<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\Entities;

use Larafony\Framework\Cache\Cache;
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
        $cacheKey = "role.{$this->id}.permissions";

        $permissionNames = Cache::instance()->remember(
            $cacheKey,
            3600, // 1 hour
            fn() => array_column($this->permissions, 'name')
        );

        return in_array($permissionName, $permissionNames, true);
    }

    /**
     * Add permission to role
     *
     * @param Permission $permission
     * @return void
     */
    public function addPermission(Permission $permission): void
    {
        /** @var \Larafony\Framework\Database\ORM\Relations\BelongsToMany $relation */
        $relation = $this->relations->getRelationInstance('permissions');
        $relation->attach([$permission->id]);

        // Invalidate cache after permission change
        $this->clearPermissionsCache();
    }

    /**
     * Remove permission from role
     *
     * @param Permission $permission
     * @return void
     */
    public function removePermission(Permission $permission): void
    {
        /** @var \Larafony\Framework\Database\ORM\Relations\BelongsToMany $relation */
        $relation = $this->relations->getRelationInstance('permissions');
        $relation->detach([$permission->id]);

        // Invalidate cache after permission change
        $this->clearPermissionsCache();
    }

    /**
     * Clear role's cached permissions and all associated users' cache
     *
     * @return void
     */
    public function clearPermissionsCache(): void
    {
        $cache = Cache::instance();

        // Clear this role's permissions cache
        $cache->forget("role.{$this->id}.permissions");

        // Clear all users with this role (they need to refresh their permissions)
        foreach ($this->users as $user) {
            $user->clearAuthCache();
        }
    }
}
