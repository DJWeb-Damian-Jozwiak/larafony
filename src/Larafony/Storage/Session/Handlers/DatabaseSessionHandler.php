<?php

declare(strict_types=1);

namespace Larafony\Framework\Storage\Session\Handlers;

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\DBAL\Models\Entities\Session;
use Larafony\Framework\Storage\Session\SessionSecurity;
use Larafony\Framework\Web\Config;

class DatabaseSessionHandler implements \SessionHandlerInterface
{
    public function __construct(
        private readonly SessionSecurity $security
    ) {
    }

    public function close(): bool
    {
        return true;
    }

    public function destroy(string $id): bool
    {
        return Session::query()->where('id', '=', $id)->delete() > 0;
    }

    public function gc(int $max_lifetime): int|false
    {
        $expired = time() - $max_lifetime;
        return Session::query()->where('last_activity', '<', $expired)->delete();
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        /** @var ?Session $session */
        $session = Session::query()->select()->where('id', '=', $id)->first();
        if (! $session) {
            return '';
        }
        $lifetime = Config::get('session.cookie_params.lifetime', 0);
        if ($session->last_activity + $lifetime < ClockFactory::timestamp()) {
            $this->destroy($id);
            return '';
        }
        return $this->security->decrypt($session->payload);
    }

    public function write(string $id, string $data): bool
    {
        $encrypted = $this->security->encrypt($data);

        /** @var ?Session $session */
        $session = Session::query()->select()->where('id', '=', $id)->first();
        $session ??= new Session();
        $session->id = $id;
        $session->payload = $encrypted;
        $session->last_activity = time();
        $session->user_ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $session->user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $session->user_id = null;

        $session->save();
        return true;
    }
}
