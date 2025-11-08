<?php

declare(strict_types=1);

namespace Larafony\Framework\Auth;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Database\ORM\Entities\User;

final class Auth
{
    private static ?ContainerContract $container = null;
    private static ?UserManager $userManager = null;
    private static ?RoleManager $roleManager = null;
    private static ?PermissionManager $permissionManager = null;

    public static function withContainer(ContainerContract $container): void
    {
        self::$container = $container;
    }

    public static function attempt(User $user, string $password, bool $remember = false): bool
    {
        return self::getUserManager()->attempt($user, $password, $remember);
    }

    public static function user(): ?User
    {
        return self::getUserManager()->user();
    }

    public static function check(): bool
    {
        return self::getUserManager()->check();
    }

    public static function guest(): bool
    {
        return self::getUserManager()->guest();
    }

    public static function login(User $user, bool $remember = false): void
    {
        self::getUserManager()->login($user, $remember);
    }

    public static function logout(): void
    {
        self::getUserManager()->logout();
    }

    public static function id(): int|string|null
    {
        return self::user()?->id;
    }

    public static function hasRole(string $role): bool
    {
        return self::getRoleManager()->hasRole($role);
    }

    /**
     * @param array<int, string> $roles
     */
    public static function hasAnyRole(array $roles): bool
    {
        return self::getRoleManager()->hasAnyRole($roles);
    }

    /**
     * @param array<int, string> $roles
     */
    public static function hasAllRoles(array $roles): bool
    {
        return self::getRoleManager()->hasAllRoles($roles);
    }

    public static function hasPermission(string $permission): bool
    {
        return self::getPermissionManager()->hasPermission($permission);
    }

    /**
     * @param array<int, string> $permissions
     */
    public static function hasAnyPermission(array $permissions): bool
    {
        return self::getPermissionManager()->hasAnyPermission($permissions);
    }

    /**
     * @param array<int, string> $permissions
     */
    public static function hasAllPermissions(array $permissions): bool
    {
        return self::getPermissionManager()->hasAllPermissions($permissions);
    }

    private static function getUserManager(): UserManager
    {
        if (self::$userManager === null) {
            if (self::$container === null) {
                throw new \RuntimeException('Container not set. Call Auth::setContainer() first.');
            }
            self::$userManager = self::$container->get(UserManager::class);
        }
        return self::$userManager;
    }

    private static function getRoleManager(): RoleManager
    {
        if (self::$roleManager === null) {
            self::$roleManager = new RoleManager(self::getUserManager());
        }
        return self::$roleManager;
    }

    private static function getPermissionManager(): PermissionManager
    {
        if (self::$permissionManager === null) {
            self::$permissionManager = new PermissionManager(self::getUserManager());
        }
        return self::$permissionManager;
    }

}
