<?php

declare(strict_types=1);

namespace Larafony\Framework\Mail\Transport;

use Larafony\Framework\Http\Factories\UriFactory;
use Larafony\Framework\Mail\Transport\ValueObjects\MailEncryption;
use Larafony\Framework\Mail\Transport\ValueObjects\MailPort;
use Larafony\Framework\Mail\Transport\ValueObjects\MailUserInfo;

/**
 * SMTP configuration from DSN.
 */
final readonly class SmtpConfig
{
    public function __construct(
        public private(set) string $host,
        public private(set) MailPort $port,
        public private(set) MailUserInfo $userInfo,
        public private(set) ?MailEncryption $encryption = null,
    ) {
    }

    public static function fromDsn(string $dsn): self
    {
        $uri = new UriFactory()->createUri($dsn);

        $encryption = MailEncryption::fromScheme($uri->getScheme());
        $port = MailPort::fromInt($uri->getPort(), $encryption);

        $userInfoString = $uri->getUserInfo();
        $userInfo = $userInfoString
            ? MailUserInfo::fromString($userInfoString)
            : new MailUserInfo();

        return new self(
            host: $uri->getHost(),
            port: $port,
            userInfo: $userInfo,
            encryption: $encryption,
        );
    }
}
