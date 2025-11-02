# Chapter 23: Sending Emails

This chapter implements a complete email system for the Larafony framework with native SMTP support built from scratch, without external dependencies.

## Overview

The Mail component provides a clean, Laravel-inspired API for sending emails with a native SMTP implementation. The architecture is interface-based, allowing for easy transport swapping in the future.

**Important Note**: Support for `symfony/mailer` will be added in the final phase of the framework/course after the official GA release of PHP 8.5 and completion of the base zero-dependency implementation. The current implementation focuses on native SMTP protocol handling.

## Features

- **Native SMTP Implementation**: RFC 5321 compliant SMTP client built from scratch
- **Interface-Based Architecture**: Easy to swap transports via `TransportContract`
- **Mailable Abstraction**: Laravel-inspired Mailable classes for email composition
- **Database Logging**: Track sent emails with `MailHistoryLogger`
- **Framework Reuse**: Leverages existing components (UriManager, Stream, ViewManager)
- **DSN Configuration**: Connection string parsing with smart defaults
- **PHP 8.5 Features**: Property hooks, `clone()` for immutability, modern syntax

## Architecture

### Contracts (Interfaces)

All interfaces use the `Contract` suffix for PHP Insights compatibility:

```php
interface TransportContract
{
    public function send(Email $message): void;
}

interface MailerContract
{
    public function send(Mailable $mailable): void;
}

interface MailHistoryLoggerContract
{
    public function log(Email $message): void;
}
```

### Value Objects

The implementation follows SOLID principles with dedicated value objects:

- **Address**: Email address with optional name, implements `\Stringable`
- **MailPort**: Smart port selection based on encryption (25/587/465)
- **MailEncryption**: SSL, TLS, or none with property hooks for checks
- **MailUserInfo**: Parses username:password from DSN
- **SmtpCommand**: Value objects for SMTP commands with validation
- **SmtpResponse**: Parses and validates SMTP response codes

### SMTP Implementation

#### SmtpConnection

Uses the existing `Stream` class from the HTTP module for socket I/O:

```php
final class SmtpConnection
{
    public bool $isConnected {
        get => !$this->stream->eof();
    }

    public static function create(string $host, int $port, int $timeout = 30): self
    {
        $resource = fsockopen($host, $port, $errno, $errstr, $timeout);
        if ($resource === false) {
            throw new TransportError("Could not connect to {$host}:{$port}");
        }
        stream_set_timeout($resource, $timeout);
        $stream = new Stream(new StreamWrapper($resource));
        return new self($stream);
    }

    public function readLine(int $length = 515): string
    {
        $line = '';
        while (!$this->stream->eof() && strlen($line) < $length) {
            $char = $this->stream->read(1);
            $line .= $char;
            if ($char === "\n") {
                break;
            }
        }
        return $line;
    }
}
```

#### SmtpResponseFactory

Implements RFC 5321 compliant multi-line response parsing:

```php
/**
 * SMTP Protocol Response Format (RFC 5321):
 * ==========================================
 *
 * Single-line response:
 * ---------------------
 * 250 OK
 * └─┬─┘ └┬┘
 *   │    └─── Message text
 *   └──────── 3-digit code followed by SPACE
 *
 * Multi-line response:
 * --------------------
 * 250-mail.example.com
 * 250-SIZE 52428800
 * 250-8BITMIME
 * 250 HELP
 * └─┬─┘ └┬┘
 *   │    └─── Last line: SPACE after code indicates end
 *   └──────── Middle lines: HYPHEN after code indicates continuation
 */
final class SmtpResponseFactory
{
    public static function readFromConnection(SmtpConnection $connection): SmtpResponse
    {
        $response = '';
        while ($line = $connection->readLine()) {
            $response .= $line;
            // Check if this is the last line (space after code instead of hyphen)
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return SmtpResponse::fromString(trim($response));
    }
}
```

#### SmtpTransport

Complete SMTP protocol implementation:

```php
final class SmtpTransport implements TransportContract
{
    public function send(Email $message): void
    {
        $this->connect();
        $this->authenticate();
        $this->sendMessage($message);
        $this->disconnect();
    }

    private function connect(): void
    {
        $this->connection = SmtpConnection::create(
            $this->config->host,
            $this->config->port->value
        );
        $this->readResponse();
        $this->executeCommand(SmtpCommand::ehlo());
    }

    private function authenticate(): void
    {
        if (!$this->config->userInfo->hasCredentials) {
            return;
        }
        $this->executeCommand(SmtpCommand::authLogin());
        $this->executeCommand(SmtpCommand::username($username));
        $this->executeCommand(SmtpCommand::password($password));
    }

    private function sendMessage(Email $message): void
    {
        // MAIL FROM
        $this->executeCommand(SmtpCommand::mailFrom($from));

        // RCPT TO (for to, cc, bcc)
        foreach ($recipients as $recipient) {
            $this->executeCommand(SmtpCommand::rcptTo($recipient->email));
        }

        // DATA
        $this->executeCommand(SmtpCommand::data());
        $this->writeData($this->buildMimeMessage($message));
        $this->executeCommand(SmtpCommand::dataEnd());
    }
}
```

### Message Building

#### Email

Immutable email message using `clone()`:

```php
final class Email
{
    public array $headers {
        get {
            $headers = [];
            if ($this->to !== []) {
                $headers[] = 'To: ' . implode(', ', $this->to);
            }
            if ($this->cc !== []) {
                $headers[] = 'Cc: ' . implode(', ', $this->cc);
            }
            if ($this->replyTo !== null) {
                $headers[] = 'Reply-To: ' . $this->replyTo;
            }
            return $headers;
        }
    }

    public function from(Address $address): self
    {
        return clone($this, ['from' => $address]);
    }

    public function to(Address $address): self
    {
        return clone($this, ['to' => [...$this->to, $address]]);
    }
}
```

#### Mailable

Abstract class for building emails:

```php
abstract class Mailable
{
    abstract protected function envelope(): Envelope;
    abstract protected function content(): Content;

    public function build(): Email
    {
        $envelope = $this->envelope();
        $content = $this->content();

        $email = new Email()
            ->from($envelope->from)
            ->subject($envelope->subject)
            ->html($content->render());

        foreach ($envelope->to as $address) {
            $email = $email->to($address);
        }

        return $email;
    }
}
```

## Usage

### Basic Configuration

Create a mailer using DSN:

```php
use Larafony\Framework\Mail\MailerFactory;

// From DSN with smart defaults
$mailer = MailerFactory::fromDsn('smtp://user:pass@smtp.example.com:587');

// MailHog for local development
$mailer = MailerFactory::createMailHogMailer('localhost', 1025);
```

DSN format: `smtp://[username:password@]host[:port]`

- Port defaults: 25 (plain), 587 (TLS), 465 (SSL)
- Encryption detected from scheme: `smtps://` → SSL, `smtp+tls://` → TLS

### Creating Mailable Classes

```php
use Larafony\Framework\Mail\Mailable;
use Larafony\Framework\Mail\Envelope;
use Larafony\Framework\Mail\Content;
use Larafony\Framework\Mail\Address;

class WelcomeEmail extends Mailable
{
    public function __construct(
        private readonly string $userName
    ) {}

    protected function envelope(): Envelope
    {
        return (new Envelope())
            ->from(new Address('noreply@example.com', 'Example App'))
            ->to(new Address('user@example.com'))
            ->subject('Welcome to Example App');
    }

    protected function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
            data: ['userName' => $this->userName]
        );
    }
}
```

### Sending Emails

```php
$mailer = MailerFactory::fromDsn($dsn);
$mailer->send(new WelcomeEmail('John Doe'));
```

### Email Logging

Track sent emails in the database:

```php
use Larafony\Framework\Mail\MailHistoryLogger;
use Larafony\Framework\Mail\Mailer;

$logger = new MailHistoryLogger();
$mailer = new Mailer($transport, $logger);

// Emails are automatically logged when sent
$mailer->send($mailable);
```

Create the mail_log table:

```bash
php bin/larafony table:mail-log
```

## SMTP Protocol Details

### Commands Implemented

- **EHLO**: Extended hello, establishes connection
- **AUTH LOGIN**: Base64-encoded authentication
- **MAIL FROM**: Specifies sender address
- **RCPT TO**: Specifies recipient addresses (to, cc, bcc)
- **DATA**: Begins message content
- **QUIT**: Closes connection

### Response Codes

- **2xx**: Success (250 OK, 220 Ready)
- **3xx**: Intermediate (354 Start mail input)
- **4xx**: Transient failure (421 Service not available)
- **5xx**: Permanent failure (550 Mailbox unavailable)

### Multi-line Response Handling

SMTP responses can span multiple lines. The implementation detects the last line by checking character at index 3:

- **Hyphen (-)**: More lines follow (e.g., `250-HELP`)
- **Space ( )**: Last line (e.g., `250 OK`)

```
250-mail.example.com
250-SIZE 52428800
250-8BITMIME
250 HELP
    ^ Space indicates this is the final line
```

## Testing

The Mail component includes 64 unit tests covering:

- Value objects (Address, MailPort, MailEncryption, MailUserInfo)
- SMTP components (SmtpCommand, SmtpResponse, SmtpConfig)
- Transport factory (MailerFactory)
- Integration tests with MailHog

Run tests:

```bash
composer test tests/Larafony/Mail/
```

## PHP 8.5 Features Used

### Property Hooks

```php
final class MailEncryption
{
    public bool $isSsl {
        get => $this->value === 'ssl';
    }

    public bool $isTls {
        get => $this->value === 'tls';
    }
}
```

### Private(set) Properties (Asymmetric Visibility)

```php
public function __construct(
    public private(set) int $value
) {}
```

### Clone with Property Override

```php
public function from(Address $address): self
{
    return clone($this, ['from' => $address]);
}
```

### Constructor Property Promotion

```php
final readonly class Address implements \Stringable
{
    public function __construct(
        public string $email,
        public ?string $name = null
    ) {}
}
```

## Important PHP 8.5 Caveats

### 1. Property Hooks and readonly are Incompatible

**Source**: [RFC: Property Hooks](https://wiki.php.net/rfc/property-hooks)

Property hooks cannot be combined with the `readonly` modifier:

```php
// ❌ This will throw a compile error
final readonly class MailPort
{
    public int $value {
        get => $this->_value;
    }
}

// ✅ Correct: Remove readonly from class
final class MailPort
{
    public int $value {
        get => $this->_value;
    }
}
```

**Reason**: Readonly properties check if backing store values are uninitialized, but virtual properties (with hooks) have no backing store. The RFC states it would be "non-obvious when readonly works on properties with hooks."

### 2. private(set) and Reference-Based Operations

**Source**: [RFC: Asymmetric Visibility v2](https://wiki.php.net/rfc/asymmetric-visibility-v2)

The most surprising caveat: **obtaining a reference to a property follows `set` visibility, not `get` visibility**. This affects operations that implicitly require references:

```php
class Envelope
{
    /**
     * @param array<int, Address> $to
     */
    public function __construct(
        public private(set) array $to = [],
    ) {}
}

// ❌ This will fail with: "Cannot indirectly modify private(set) property"
$envelope = new Envelope();
array_walk($envelope->to, function (Address &$address) {
    // array_walk requires a reference, which needs set visibility
});

// ❌ These also fail from public scope:
$envelope->to[] = $address;        // Array append requires reference
$envelope->to['key'] = $address;   // Array modification requires reference

// ✅ This works - foreach only reads values:
foreach ($envelope->to as $address) {
    // Reading is allowed with get visibility
}
```

**Why this happens**: Writing to an array property technically involves obtaining a reference to it first. Since reference access follows `set` visibility (not `get` visibility), operations requiring references are restricted by the `set` visibility level.

**Practical impact**: In our `Mailable::build()` method (src/Larafony/Mail/Mailable.php:35), we use `foreach` loops instead of `array_walk()` to iterate over envelope recipients:

```php
// Cannot use array_walk() with private(set) properties
// See RFC: references follow set visibility, not get visibility
foreach ($envelope->to as $address) {
    $email = $email->to($address);
}
```

**Summary**: This design decision in PHP prevents bypassing write-scope controls through reference-based operations, maintaining the security guarantees of asymmetric visibility. Always use `foreach` for read-only iteration over `private(set)` array properties.

## Framework Component Reuse

The Mail component demonstrates excellent framework integration:

- **UriManager**: DSN parsing with scheme detection
- **Stream**: Socket I/O from HTTP module
- **ViewManager**: Email template rendering
- **Application Container**: Dependency injection
- **DBAL Models**: Email logging with ORM

## Future Enhancements

After PHP 8.5 GA release and base implementation completion:

- **Symfony Mailer Integration**: Add `symfony/mailer` as optional transport
- **Additional Transports**: AWS SES, SendGrid, Mailgun drivers
- **Attachments**: File attachment support
- **Multipart**: HTML/text multipart messages
- **Inline Images**: Embedded image support
- **Queue Integration**: Async email sending
- **Testing Utilities**: Fake mailer for unit tests

## Files Structure

```
src/Larafony/Mail/
├── Contracts/
│   ├── TransportContract.php
│   ├── MailerContract.php
│   └── MailHistoryLoggerContract.php
├── Message/
│   └── Email.php
├── Transport/
│   ├── ValueObjects/
│   │   ├── MailPort.php
│   │   ├── MailEncryption.php
│   │   └── MailUserInfo.php
│   ├── Assert/
│   │   ├── NotConnected.php
│   │   ├── CommandLengthIsValid.php
│   │   └── ResponseCodeIsValid.php
│   ├── SmtpConnection.php
│   ├── SmtpCommand.php
│   ├── SmtpResponse.php
│   ├── SmtpResponseFactory.php
│   ├── SmtpConfig.php
│   └── SmtpTransport.php
├── Exceptions/
│   └── TransportError.php
├── Address.php
├── Envelope.php
├── Content.php
├── Mailable.php
├── Mailer.php
├── MailerFactory.php
└── MailHistoryLogger.php

Console/Commands/
└── MailLogTable.php

DBAL/Models/Entities/
└── MailLog.php

stubs/
└── mail_log_migration.stub

tests/Larafony/Mail/
├── AddressTest.php
├── EnvelopeTest.php
├── Transport/
│   ├── ValueObjects/
│   │   ├── MailEncryptionTest.php
│   │   ├── MailPortTest.php
│   │   └── MailUserInfoTest.php
│   ├── SmtpCommandTest.php
│   ├── SmtpResponseTest.php
│   └── SmtpConfigTest.php
├── MailerFactoryTest.php
└── MailerIntegrationTest.php
```

## Key Takeaways

1. **Zero Dependencies**: Complete SMTP implementation without external libraries
2. **PSR Compliance**: Interface-based design following PSR principles
3. **Modern PHP**: Extensive use of PHP 8.5 features (property hooks, clone, private(set))
4. **SOLID Design**: Value objects with single responsibilities
5. **Framework Integration**: Excellent reuse of existing components
6. **RFC Compliant**: Follows SMTP RFC 5321 specification
7. **Well Tested**: 64 unit tests ensuring reliability
8. **Extensible**: Easy to add new transports via `TransportContract`

## Resources

- [RFC 5321 - Simple Mail Transfer Protocol](https://datatracker.ietf.org/doc/html/rfc5321)
- [RFC 2045 - MIME Part One: Format of Internet Message Bodies](https://datatracker.ietf.org/doc/html/rfc2045)
- [PHP fsockopen() Documentation](https://www.php.net/manual/en/function.fsockopen.php)
- [MailHog - Email testing tool](https://github.com/mailhog/MailHog)
