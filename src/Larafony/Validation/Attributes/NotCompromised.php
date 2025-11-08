<?php

declare(strict_types=1);

namespace Larafony\Framework\Validation\Attributes;

use Attribute;
use Larafony\Framework\Http\Client\Config\HttpClientConfig;
use Larafony\Framework\Http\Client\HttpClientFactory;
use Larafony\Framework\Http\Request;
use Larafony\Framework\Validation\Contracts\ValidationRuleContract;

/**
 * Validates that a password has not been compromised in known data breaches.
 *
 * Uses the Have I Been Pwned (HIBP) Pwned Passwords API v3 with k-anonymity.
 * Only the first 5 characters of the SHA-1 hash are sent to the API.
 *
 * @see https://haveibeenpwned.com/API/v3#PwnedPasswords
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class NotCompromised implements ValidationRuleContract
{
    private const string API_URL = 'https://api.pwnedpasswords.com/range/';

    public function __construct(
        private readonly int $threshold = 0,
        private readonly string $message = 'This password has appeared in data breaches and must not be used.'
    ) {
    }

    public function validate(mixed $value, string $field): bool
    {
        if (!is_string($value) || $value === '') {
            return false;
        }

        // Hash password with SHA-1 and split using pipe
        $hash = $value |> (static fn($x) => sha1($x)) |> (static fn($x) => strtoupper($x));
        $prefix = substr($hash, 0, 5);
        $suffix = substr($hash, 5);

        // Query HIBP API with k-anonymity (only send prefix)
        $response = $this->queryPwnedPasswordsApi($prefix);

        if ($response === null) {
            // API error - fail open for better UX (or fail closed for security)
            return true; // Allowing when API is down
        }

        // Parse response using pipes: split lines, check each for match
        $lines = $response
            |> (static fn($x) => explode("\r\n", $x))
            |> (static fn($x) => array_filter($x, fn($line) => str_contains($line, ':')))
            |> (static fn($x) => array_map(fn($line) => explode(':', $line), $x));

        return $this->checkCompromised($suffix, $lines, $this->threshold);
    }

    public function message(): string
    {
        return $this->message;
    }

    /**
     * @param array<array{string, string}> $lines
     */
    private function checkCompromised(string $suffix, array $lines, int $threshold): bool
    {
        foreach ($lines as [$hashSuffix, $count]) {
            if ($hashSuffix === $suffix) {
                // Found in breach! Check against threshold
                return (int)$count <= $threshold;
            }
        }

        // Not found in breaches - safe password
        return true;
    }

    private function queryPwnedPasswordsApi(string $prefix): ?string
    {
        try {
            $request = Request::create(
                uri: self::API_URL . $prefix,
                method: 'GET',
                headers: [
                    'User-Agent' => 'Larafony-Framework-Password-Validator',
                    'Add-Padding' => 'true' // HIBP API feature for additional privacy
                ]
            );

            $config = HttpClientConfig::withTimeout(3);
            $client = HttpClientFactory::instance();

            $response = $client->sendRequest($request);

            return $response->getStatusCode() === 200
                ? $response->getBody()->getContents()
                : null;

        } catch (\Throwable) {
            // API error, network timeout, etc - fail open
            return null;
        }
    }
}
