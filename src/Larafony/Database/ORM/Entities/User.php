<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\Entities;

use Larafony\Framework\Clock\Contracts\Clock;
use Larafony\Framework\Database\ORM\Attributes\BelongsToMany;
use Larafony\Framework\Database\ORM\Model;

class User extends Model
{
    public ?string $email {
        get => $this->email;
        set {
            $this->email = $value;
            $this->markPropertyAsChanged('email');
        }
    }
    public ?string $username {
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
    public ?Clock $last_login_at {
        get => $this->last_login_at;
        set {
            $this->last_login_at = $value;
            $this->markPropertyAsChanged('last_login_at');
        }
    }
    public Clock $created_at {
        get => $this->created_at;
        set {
            $this->created_at = $value;
            $this->markPropertyAsChanged('created_at');
        }
    }
    public Clock $updated_at {
        get => $this->updated_at;
        set {
            $this->updated_at = $value;
            $this->markPropertyAsChanged('updated_at');
        }
    }

    /**
     * @return array<Role>
     */
    #[BelongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')]
    public array $roles {
        get => $this->relations->getRelation('roles');
    }

    public function addRole(Role $role): void
    {
        if ($this->hasRole($role->name)) {
            return;
        }
        /** @var \Larafony\Framework\Database\ORM\Relations\BelongsToMany $relation */
        $relation = $this->relations->getRelationInstance('roles');
        $relation->attach([$role->id]);
    }

    public function hasRole(string $roleName): bool
    {
        return array_any($this->roles, static fn (Role $role) => $role->name === $roleName);
    }

    public function hasPermission(string $permissionName): bool
    {
        return array_any($this->roles, static fn (Role $role) => $role->hasPermission($permissionName));
    }

    protected array $casts = [
        'password_reset_expires' => 'datetime',
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
