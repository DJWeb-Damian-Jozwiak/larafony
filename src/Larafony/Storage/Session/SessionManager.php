<?php

declare(strict_types=1);

namespace Larafony\Framework\Storage\Session;

use Larafony\Framework\Storage\Session\Handlers\DatabaseSessionHandler;
use Larafony\Framework\Storage\Session\Handlers\FileSessionHandler;
use Larafony\Framework\Web\Config;

final class SessionManager
{
    private bool $started = false;
    /**
     * @var array<string, mixed>
     */
    private array $options;

    private \SessionHandlerInterface $handler;

    public function __construct(private readonly SessionConfiguration $config)
    {
        $this->options = (array) Config::get('session.cookie_params');
        $this->handler = $this->config->getHandler(Config::get('session.handler'));
    }

    public static function create(): self
    {
        $path = Config::get('session.path');
        $configSession = new SessionConfiguration();
        $security = new SessionSecurity();
        $configSession->registerHandler(new FileSessionHandler($path, $security));
        $configSession->registerHandler(new DatabaseSessionHandler($security));
        $manager = new SessionManager($configSession);
        $manager->start();
        return $manager;
    }

    public function start(): bool
    {
        if ($this->started) {
            return true;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            return true;
        }
        session_name('PHPSESSID');
        session_set_cookie_params($this->options);
        session_set_save_handler($this->handler, true);
        $this->started = true;
        return true;
    }

    public function getId(): string
    {
        return session_id();
    }

    /** @codeCoverageIgnore  */
    public function regenerateId(bool $deleteOldSession = false): bool
    {
        return session_regenerate_id($deleteOldSession);
    }

    /** @codeCoverageIgnore  */
    public function destroy(): bool
    {
        if (! $this->started) {
            return false;
        }

        $this->started = false;
        return session_destroy();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $_SESSION;
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function clear(): void
    {
        $_SESSION = [];
    }
}
