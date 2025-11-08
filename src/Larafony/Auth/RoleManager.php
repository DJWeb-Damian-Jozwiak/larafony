<?php

declare(strict_types=1);

namespace Larafony\Framework\Auth;

final readonly class RoleManager
{
    public function __construct(private UserManager $userManager)
    {
    }

    public function hasRole(string $role): bool
    {
        return $this->userManager->check() && $this->userManager->user()?->hasRole($role);
    }

    /**
     * @param array<int, string> $roles
     *
     * @return bool
     */
    public function hasAnyRole(array $roles): bool
    {
        if (! $this->userManager->check()) {
            return false;
        }

        return array_any($roles, fn (string $role) => $this->hasRole($role));
    }

    /**
     * @param array<int, string> $roles
     *
     * @return bool
     */
    public function hasAllRoles(array $roles): bool
    {
        if (! $this->userManager->check()) {
            return false;
        }

        return array_all($roles, fn (string $role) => $this->hasRole($role));
    }
}
