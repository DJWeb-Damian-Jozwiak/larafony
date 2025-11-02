# Chapter 22: Encrypted Cookies and Sessions

> Part of the Larafony Framework - A modern PHP 8.5 framework built from scratch
>
> 📚 Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)

## Overview

This chapter implements a complete security layer for session management and cookie handling using modern cryptography. Unlike Laravel's OpenSSL-based AES-256-CBC encryption, Larafony uses **libsodium's XChaCha20-Poly1305 AEAD cipher** — a modern, authenticated encryption algorithm that provides both confidentiality and integrity in a single operation.

The implementation follows a constraint-driven design pattern with specialized assertion classes for validation, ensuring cryptographic operations fail safely and predictably. Session management supports both file-based and database-backed storage with automatic encryption, while cookies are transparently encrypted using the same secure infrastructure.

This security-first approach integrates seamlessly with Larafony's existing ORM, Clock abstraction, and configuration system, providing production-ready encrypted storage with comprehensive test coverage (25 test methods across 4 test suites).

## Key Components

### Encryption System

- **EncryptionService** - Core encryption service using XChaCha20-Poly1305 AEAD cipher from libsodium (`src/Larafony/Encryption/EncryptionService.php:14`)
  - Uses `#[SensitiveParameter]` attribute for secure parameter handling
  - Validates encryption keys through specialized assertion classes
  - Encrypts data with authentication in a single operation (AEAD)

- **KeyGenerator** - Cryptographically secure key generation (`src/Larafony/Encryption/KeyGenerator.php`)
  - Generates 32-byte keys using `sodium_crypto_aead_xchacha20poly1305_ietf_keygen()`
  - Returns base64-encoded keys for storage in configuration

- **Constraint Assertions** - Five specialized validation classes in `Encryption/Assert` namespace following Single Responsibility Principle:
  - `EncryptionKeyExists` - Validates encryption key presence
  - `KeyLengthIsValid` - Validates decoded key is exactly 32 bytes
  - `Base64IsValid` - Validates base64 decoding success (with `@phpstan-assert`)
  - `DataLengthIsValid` - Validates minimum data length for nonce extraction
  - `DecryptionSucceeded` - Validates decryption success (with `@phpstan-assert`)

### Session Management

- **SessionManager** - Main session facade with PSR-20 clock integration (`src/Larafony/Storage/Session/SessionManager.php:11`)
  - Factory method `create()` for automatic handler registration
  - Supports file and database session handlers via SessionHandlerInterface
  - Standard session operations: get, set, has, remove, clear, regenerateId

- **SessionConfiguration** - Handler registry and configuration (`src/Larafony/Storage/Session/SessionConfiguration.php`)
  - Registers multiple session handlers
  - Selects handler based on configuration

- **SessionSecurity** - Session data encryption wrapper (`src/Larafony/Storage/Session/SessionSecurity.php`)
  - Encrypts/decrypts session payloads using EncryptionService
  - Used by both file and database handlers

- **FileSessionHandler** - File-based session storage with encryption (`src/Larafony/Storage/Session/Handlers/FileSessionHandler.php`)
  - Implements `\SessionHandlerInterface`
  - Automatic garbage collection of expired sessions
  - Encrypted session data stored as files

- **DatabaseSessionHandler** - Database-backed session storage with ORM integration (`src/Larafony/Storage/Session/Handlers/DatabaseSessionHandler.php:12`)
  - Uses Session model with property hooks for persistence
  - Tracks user IP, user agent, and user ID
  - Automatic expiration checking with ClockFactory

### Cookie Management

- **CookieManager** - Encrypted cookie handling with automatic encryption/decryption (`src/Larafony/Storage/CookieManager.php:10`)
  - Transparently encrypts cookie values before storage
  - Decrypts on retrieval using EncryptionService
  - Supports bulk operations with `all()` method

- **CookieOptions** - Cookie configuration using PHP 8.5 readonly class (`src/Larafony/Storage/CookieOptions.php:9`)
  - Secure defaults (HttpOnly, SameSite=Lax, Secure on HTTPS)
  - Integration with ClockFactory for expiration times
  - Automatic HTTPS detection for secure flag

### Database Infrastructure

- **Session Model** - ORM entity with property hooks (`src/Larafony/DBAL/Models/Entities/Session.php`)
  - Non-incrementing string primary key (session ID)
  - Tracks payload, last_activity, user_ip, user_agent, user_id
  - Property hooks for change tracking

- **SessionTable Command** - Migration generator for sessions table (`src/Larafony/Console/Commands/SessionTable.php:13`)
  - Attribute-based command registration: `#[AsCommand(name: 'table:session')]`
  - Generates timestamped migration from stub template
  - Creates optimized database schema with indexes on last_activity and user_id

## PSR Standards Implemented

- **PSR-20 (Clock)**: Clock abstraction used via ClockFactory for testable time-based operations in cookie expiration and session validation (`src/Larafony/Storage/CookieOptions.php:23`, `src/Larafony/Storage/Session/Handlers/DatabaseSessionHandler.php:48`)

- **No new PSR standards**: This chapter focuses on security implementation using existing PSR infrastructure from previous chapters

## New Attributes

- `#[SensitiveParameter]` - Native PHP 8.2+ attribute used in `EncryptionService::encrypt()` to redact sensitive values from stack traces (`src/Larafony/Encryption/EncryptionService.php:34`)

## Usage Examples

### Key Generation

First, generate a secure encryption key:

```bash
php bin/console key:generate
```

This command automatically generates a cryptographically secure 32-byte key using libsodium and stores it in your `.env` file as `APP_KEY`.

### Basic Encryption

```php
<?php

use Larafony\Framework\Encryption\EncryptionService;

// Encrypt sensitive data
$encryptor = new EncryptionService();
$encrypted = $encryptor->encrypt(['password' => 'secret123']);

// Decrypt data
$decrypted = $encryptor->decrypt($encrypted);
// Result: ['password' => 'secret123']
```

### Cookie Management

```php
<?php

use Larafony\Framework\Storage\CookieManager;
use Larafony\Framework\Storage\CookieOptions;

$cookies = new CookieManager();

// Set encrypted cookie with custom options
$cookies->set('user_preferences', [
    'theme' => 'dark',
    'language' => 'en'
], new CookieOptions(
    expires: time() + 86400, // 24 hours
    path: '/',
    secure: true,
    httponly: true,
    samesite: 'Strict'
));

// Retrieve and decrypt automatically
$preferences = $cookies->get('user_preferences');
// Result: ['theme' => 'dark', 'language' => 'en']

// Remove cookie
$cookies->remove('user_preferences');
```

### Session Management

```php
<?php

use Larafony\Framework\Storage\Session\SessionManager;

// Create and start session with automatic handler selection
$session = SessionManager::create();

// Store data (automatically encrypted)
$session->set('user_id', 42);
$session->set('cart', ['item1', 'item2', 'item3']);

// Retrieve data
$userId = $session->get('user_id'); // 42
$cart = $session->get('cart', []); // ['item1', 'item2', 'item3']

// Check existence
if ($session->has('user_id')) {
    // User is logged in
}

// Remove specific item
$session->remove('cart');

// Clear all session data
$session->clear();

// Regenerate session ID (security best practice after login)
$session->regenerateId(deleteOldSession: true);
```

### Database Session Setup

```php
<?php

// Step 1: Generate migration
// Run: php bin/console table:session

// Step 2: Run migration
// Run: php bin/console migrate

// Step 3: Configure database sessions in config/session.php
return [
    'handler' => \Larafony\Framework\Storage\Session\Handlers\DatabaseSessionHandler::class,
    'path' => '/tmp/sessions', // Fallback for file handler
    'cookie_params' => [
        'lifetime' => 7200, // 2 hours
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]
];

// Sessions are now stored in database with encryption
$session = SessionManager::create();
$session->set('data', 'value'); // Encrypted and stored in sessions table
```

## Comparison with Other Frameworks

| Feature | Larafony | Laravel | Symfony |
|---------|----------|---------|---------|
| **Encryption Cipher** | XChaCha20-Poly1305 (AEAD) | AES-256-CBC | Various (via bundles) |
| **Cryptographic Library** | libsodium (PHP extension) | OpenSSL | libsodium (for secrets) |
| **Authentication** | Built-in (AEAD) | Separate MAC signature | Varies by bundle |
| **Key Generation** | `php bin/console key:generate` | `php artisan key:generate` | `secrets:generate-keys` |
| **Key Format** | Base64-encoded 32 bytes | Base64-encoded 32 bytes | Asymmetric key pair |
| **Session Encryption** | Automatic (both handlers) | Via middleware/config | Manual via bundles |
| **Cookie Encryption** | Automatic in CookieManager | Via EncryptCookies middleware | Manual via bundles |
| **Session Handlers** | File, Database | File, Database, Redis, Array | File, PDO, Redis, Memcached |
| **Database Schema** | ORM model with migrations | Migration-based | Doctrine-based or manual |
| **Validation Approach** | Constraint assertion classes | Exception throwing | Constraint system (validation) |
| **PSR Compliance** | PSR-20 (Clock) | Limited PSR compliance | Extensive PSR compliance |
| **Configuration** | Centralized config files | `.env` + config files | `.env` + YAML/PHP config |

**Key Differences:**

- **Modern Cryptography**: Larafony uses XChaCha20-Poly1305, a modern AEAD cipher that combines encryption and authentication in a single operation, while Laravel uses the older AES-256-CBC with separate HMAC. XChaCha20-Poly1305 is faster, more secure against timing attacks, and harder to misuse.

- **Constraint-Driven Validation**: Larafony implements five specialized assertion classes (SRP compliance) with PHPStan type narrowing support, providing clear error messages and compile-time verification. Laravel throws generic exceptions, while Symfony bundles vary in approach.

- **Transparent Encryption**: Both session handlers automatically encrypt data without middleware configuration. Laravel requires `EncryptCookies` middleware to be registered and configured, while Symfony typically requires third-party bundles (specshaper/encrypt-bundle, michaeldegroot/doctrine-encrypt-bundle).

- **ORM Integration**: Larafony's DatabaseSessionHandler uses the native ORM with property hooks (PHP 8.4+), providing type-safe database access. Laravel uses Eloquent, Symfony typically uses Doctrine or manual queries.

- **Clock Abstraction**: Uses PSR-20 ClockFactory for testable time operations (cookie expiration, session timeout). Laravel uses Carbon directly, Symfony may use custom abstractions or DateTime.

- **Security Defaults**: CookieOptions provides secure defaults (HttpOnly, Secure on HTTPS, SameSite=Lax) with readonly class pattern. All frameworks offer similar security, but implementation approaches differ.

- **Key Management**: Larafony and Laravel use symmetric keys (single APP_KEY), while Symfony's secrets system uses asymmetric cryptography (public/private key pairs) for environment-specific secret management. Symfony's approach is more complex but allows committing encrypted secrets to version control.

---

📚 **Learn More:** This implementation is explained in detail with step-by-step tutorials, tests, and best practices at [masterphp.eu](https://masterphp.eu)
