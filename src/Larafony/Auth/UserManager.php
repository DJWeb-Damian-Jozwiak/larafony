<?php

declare(strict_types=1);

namespace Larafony\Framework\Auth;

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Database\ORM\Entities\User;
use Larafony\Framework\Storage\CookieManager;
use Larafony\Framework\Storage\CookieOptions;
use Larafony\Framework\Storage\Session\SessionManager;

class UserManager
{
    private const string SESSION_KEY = 'auth_user_id';
    private const string REMEMBER_TOKEN_COOKIE = 'remember_token';
    private const int REMEMBER_COOKIE_EXPIRY = 60; // 60 days

    private ?User $user = null;

    public function __construct(
        private readonly SessionManager $session,
        private readonly CookieManager $cookies
    ) {
    }

    public function attempt(User $user, string $password, bool $remember = false): bool
    {
        if (! password_verify($password, $user->password)) {
            return false;
        }

        if (! $user->is_active) {
            return false;
        }

        $this->login($user, $remember);
        return true;
    }

    public function user(): ?User
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $userId = $this->session->get(self::SESSION_KEY);

        if ($userId) {
            /** @var ?User $user */
            $user = User::query()->select()->where('id', '=', $userId)->first();
            $this->user = $user;
            return $this->user;
        }

        $rememberToken = $this->cookies->get(self::REMEMBER_TOKEN_COOKIE);
        if ($rememberToken) {
            /** @var ?User $user */
            $user = User::query()->select()->where('remember_token', '=', $rememberToken)->first();
            if ($user instanceof User) {
                $this->login($user, true);
                return $user;
            }
            $this->cookies->remove(self::REMEMBER_TOKEN_COOKIE);
        }

        return null;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return ! $this->check();
    }

    public function login(User $user, bool $remember = false): void
    {
        // Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(delete_old_session: true);

        $this->user = $user;
        $this->session->set(self::SESSION_KEY, $user->id);

        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $user->fill(['remember_token' => $token])->save();

            $this->cookies->set(
                self::REMEMBER_TOKEN_COOKIE,
                $token,
                new CookieOptions(expires: ClockFactory::now()->modify('+' . self::REMEMBER_COOKIE_EXPIRY . ' days')->getTimestamp())
            );
        }
    }

    public function logout(): void
    {
        $this->user?->fill(['remember_token' => null])->save();

        $this->user = null;
        $this->session->remove(self::SESSION_KEY);
        $this->cookies->remove(self::REMEMBER_TOKEN_COOKIE);
    }
}
