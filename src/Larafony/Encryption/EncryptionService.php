<?php

declare(strict_types=1);

namespace Larafony\Framework\Encryption;

use Larafony\Framework\Encryption\Assert\Base64IsValid;
use Larafony\Framework\Encryption\Assert\DataLengthIsValid;
use Larafony\Framework\Encryption\Assert\DecryptionSucceeded;
use Larafony\Framework\Encryption\Assert\EncryptionKeyExists;
use Larafony\Framework\Encryption\Assert\KeyLengthIsValid;
use Larafony\Framework\Web\Config;

final class EncryptionService
{
    private const NONCE_LENGTH = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;
    private const KEY_LENGTH = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES;

    private readonly string $key;

    public function __construct()
    {
        $key = Config::get('app.key');

        EncryptionKeyExists::assert($key);

        $decodedKey = base64_decode($key);

        KeyLengthIsValid::assert($decodedKey, self::KEY_LENGTH);

        $this->key = $decodedKey;
    }

    public function encrypt(#[\SensitiveParameter] mixed $value): string
    {
        $nonce = random_bytes(self::NONCE_LENGTH);
        $serialized = serialize($value);

        $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
            $serialized,
            '',
            $nonce,
            $this->key
        );

        $merged = $nonce . $ciphertext;
        return base64_encode($merged);
    }

    public function decrypt(string $encrypted): mixed
    {
        $decoded = base64_decode($encrypted, true);

        Base64IsValid::assert($decoded);

        DataLengthIsValid::assert($decoded, self::NONCE_LENGTH);

        $nonce = substr($decoded, 0, self::NONCE_LENGTH);
        $ciphertext = substr($decoded, self::NONCE_LENGTH);

        $decrypted = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
            $ciphertext,
            '',
            $nonce,
            $this->key
        );

        DecryptionSucceeded::assert($decrypted);

        return unserialize($decrypted);
    }
}
