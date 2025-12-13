<?php

declare(strict_types=1);

namespace Larafony\Framework\Auth;

final readonly class PermissionManager
{
    public function __construct(private UserManager $userManager)
    {
    }

    public function hasPermission(string $permission): bool
    {
        return $this->userManager->check() && $this->userManager->user()?->hasPermission($permission);
    }

    /**
     * @param array<int, string> $permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        if (! $this->userManager->check()) {
            return false;
        }

        return array_any($permissions, fn (string $permission) => $this->hasPermission($permission));
    }

    /**
     * @param array<int, string> $permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        if (! $this->userManager->check()) {
            return false;
        }

        return array_all($permissions, fn (string $permission) => $this->hasPermission($permission));
    }
}
