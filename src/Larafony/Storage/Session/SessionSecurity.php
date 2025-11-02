<?php

declare(strict_types=1);

namespace Larafony\Framework\Storage\Session;

use Larafony\Framework\Encryption\EncryptionService;

final class SessionSecurity
{
    public function encrypt(#[\SensitiveParameter] mixed $data): string
    {
        return new EncryptionService()->encrypt($data);
    }

    public function decrypt(string $data): mixed
    {
        return new EncryptionService()->decrypt($data);
    }
}
