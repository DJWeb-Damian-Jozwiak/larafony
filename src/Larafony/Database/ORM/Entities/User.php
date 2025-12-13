<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\Entities;

use Larafony\Framework\Cache\Cache;
use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Clock\Contracts\Clock;
use Larafony\Framework\Database\ORM\Attributes\BelongsToMany;
use Larafony\Framework\Database\ORM\Attributes\CastUsing;
use Larafony\Framework\Database\ORM\Model;

class User extends Model
{
    public string $email {
        get => $this->email;
        set {
            $this->email = $value;
            $this->markPropertyAsChanged('email');
        }
    }
    public string $username {
        get => $this->username;
        set {
            $this->username = $value;
            $this->markPropertyAsChanged('username');
        }
    }
    public string $password {
        get => $this->password;
        set {
            // Auto-hash password if not already hashed (Argon2id)
            $this->password = str_starts_with($value, '$argon2') ? $value : password_hash($value, PASSWORD_ARGON2ID);
            $this->markPropertyAsChanged('password');
        }
    }
    public ?string $remember_token {
        get => $this->remember_token;
        set {
            $this->remember_token = $value;
            $this->markPropertyAsChanged('remember_token');
        }
    }
    public ?string $password_reset_token {
        get => $this->password_reset_token;
        set {
            $this->password_reset_token = $value;
            $this->markPropertyAsChanged('password_reset_token');
        }
    }
    public ?Clock $password_reset_expires {
        get => $this->password_reset_expires;
        set {
            $this->password_reset_expires = $value;
            $this->markPropertyAsChanged('password_reset_expires');
        }
    }
    public ?Clock $email_verified_at {
        get => $this->email_verified_at;
        set {
            $this->email_verified_at = $value;
            $this->markPropertyAsChanged('email_verified_at');
        }
    }
    public int $is_active {
        get => $this->is_active;
        set {
            $this->is_active = $value;
            $this->markPropertyAsChanged('is_active');
        }
    }
    #[CastUsing(ClockFactory::parse(...))]
    public ?Clock $last_login_at {
        get => $this->last_login_at;
        set {
            $this->last_login_at = $value;
            $this->markPropertyAsChanged('last_login_at');
        }
    }
    #[CastUsing(ClockFactory::parse(...))]
    public Clock $created_at {
        get => $this->created_at;
        set {
            $this->created_at = $value;
            $this->markPropertyAsChanged('created_at');
        }
    }

    #[CastUsing(ClockFactory::parse(...))]
    public Clock $updated_at {
        get => $this->updated_at;
        set {
            $this->updated_at = $value;
            $this->markPropertyAsChanged('updated_at');
        }
    }

    /**
     * @var array<int, Role> $roles
     */
    #[BelongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')]
    public array $roles {
        get => $this->relations->getRelation('roles');
        set => $this->syncRoles($value);
    }

    /**
     * @param array<int, Role> $roles
     *
     * @return array<int, Role>
     */
    public function syncRoles(array $roles): array
    {
        /** @var \Larafony\Framework\Database\ORM\Relations\BelongsToMany $relation */
        $relation = $this->relations->getRelationInstance('roles');
        $roleIds = array_map(static fn (Role $role) => $role->id, $roles);
        $relation->sync($roleIds);
        return $roles;
    }

    public function addRole(Role $role): void
    {
        if ($this->hasRole($role->name)) {
            return;
        }
        /** @var \Larafony\Framework\Database\ORM\Relations\BelongsToMany $relation */
        $relation = $this->relations->getRelationInstance('roles');
        $relation->attach([$role->id]);

        // Invalidate cache after role change
        $this->clearAuthCache();
    }

    public function removeRole(Role $role): void
    {
        /** @var \Larafony\Framework\Database\ORM\Relations\BelongsToMany $relation */
        $relation = $this->relations->getRelationInstance('roles');
        $relation->detach([$role->id]);

        // Invalidate cache after role change
        $this->clearAuthCache();
    }

    public function hasRole(string $roleName): bool
    {
        $cacheKey = "user.{$this->id}.roles";

        $roleNames = Cache::instance()->remember(
            $cacheKey,
            3600, // 1 hour
            fn () => array_map(static fn (Role $role) => $role->name, $this->roles),
        );

        return in_array($roleName, $roleNames, true);
    }

    public function hasPermission(string $permissionName): bool
    {
        $cacheKey = "user.{$this->id}.permissions";

        $permissions = Cache::instance()->remember(
            $cacheKey,
            3600, // 1 hour
            function () {
                $allPermissions = [];
                foreach ($this->roles as $role) {
                    foreach ($role->permissions as $permission) {
                        $allPermissions[] = $permission->name;
                    }
                }
                return array_unique($allPermissions);
            },
        );

        return in_array($permissionName, $permissions, true);
    }

    /**
     * Clear user's cached roles and permissions
     *
     * @return void
     */
    public function clearAuthCache(): void
    {
        $cache = Cache::instance();
        $cache->forget("user.{$this->id}.roles");
        $cache->forget("user.{$this->id}.permissions");
    }
}
