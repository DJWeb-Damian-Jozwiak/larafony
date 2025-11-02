<?php

declare(strict_types=1);

namespace Larafony\Framework\Encryption;

final class KeyGenerator
{
    public function generateKey(): string
    {
        $key = sodium_crypto_aead_xchacha20poly1305_ietf_keygen();
        return base64_encode($key);
    }
}
