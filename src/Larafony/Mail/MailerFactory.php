<?php

declare(strict_types=1);

namespace Larafony\Framework\Mail;

use Larafony\Framework\Mail\Transport\SmtpConfig;
use Larafony\Framework\Mail\Transport\SmtpTransport;
use Larafony\Framework\View\ViewManager;

/**
 * Factory for creating Mailer instances.
 */
final class MailerFactory
{
    public static function fromDsn(string $dsn, ViewManager $viewManager): Mailer
    {
        $config = SmtpConfig::fromDsn($dsn);
        $transport = new SmtpTransport($config);

        return new Mailer($transport, $viewManager);
    }

    public static function createSmtpMailer(
        ViewManager $viewManager,
        string $host,
        int $port,
        ?string $username = null,
        ?string $password = null,
        ?string $encryption = null,
    ): Mailer {
        $scheme = match ($encryption) {
            'ssl' => 'smtps',
            'tls' => 'smtp+tls',
            default => 'smtp',
        };

        $auth = $username && $password
            ? urlencode($username) . ':' . urlencode($password) . '@'
            : '';

        $dsn = "{$scheme}://{$auth}{$host}:{$port}";

        return self::fromDsn($dsn, $viewManager);
    }

    public static function createMailHogMailer(
        ViewManager $viewManager,
        string $host = 'localhost',
        int $port = 1025
    ): Mailer {
        return self::fromDsn("smtp://{$host}:{$port}", $viewManager);
    }
}
