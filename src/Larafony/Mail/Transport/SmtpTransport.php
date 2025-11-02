<?php

declare(strict_types=1);

namespace Larafony\Framework\Mail\Transport;

use Larafony\Framework\Mail\Contracts\TransportContract;
use Larafony\Framework\Mail\Exceptions\TransportError;
use Larafony\Framework\Mail\Message\Email;
use Larafony\Framework\Mail\Transport\Assert\NotConnected;

/**
 * SMTP transport implementation.
 */
final class SmtpTransport implements TransportContract
{
    private ?SmtpConnection $connection = null;

    public function __construct(
        private readonly SmtpConfig $config
    ) {
    }

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
        if (! $this->config->userInfo->hasCredentials) {
            return;
        }

        $usernameMsg = 'Username is required for authentication';
        $passwordMsg = 'Password is required for authentication';

        $username = $this->config->userInfo->username ?? throw new TransportError($usernameMsg);
        $password = $this->config->userInfo->password ?? throw new TransportError($passwordMsg);

        $this->executeCommand(SmtpCommand::authLogin());
        $this->executeCommand(SmtpCommand::username($username));
        $this->executeCommand(SmtpCommand::password($password));
    }

    private function sendMessage(Email $message): void
    {
        // MAIL FROM
        $from = $message->from->email ?? throw new TransportError('From address is required');
        $this->executeCommand(SmtpCommand::mailFrom($from));

        $data = [
            ...$message->to,
            ...$message->cc,
            ...$message->bcc,
        ];
        foreach ($data as $recipient) {
            $this->executeCommand(SmtpCommand::rcptTo($recipient->email));
        }

        // DATA
        $this->executeCommand(SmtpCommand::data());
        $this->writeData($this->buildMimeMessage($message));
        $this->executeCommand(SmtpCommand::dataEnd());
    }

    private function buildMimeMessage(Email $message): string
    {
        $headers = [];
        $headers[] = 'From: ' . $message->from;
        $headers[] = 'Subject: ' . $message->subject;
        $headers = [...$headers, ...$message->headers];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: quoted-printable';

        $body = quoted_printable_encode($message->htmlBody ?? '');

        return implode("\r\n", $headers) . "\r\n\r\n" . $body;
    }

    private function disconnect(): void
    {
        $this->executeCommand(SmtpCommand::quit());
        $this->connection?->close();
    }

    private function executeCommand(SmtpCommand $command): void
    {
        $this->writeData($command->value);
        $this->readResponse()->assertSuccess();
    }

    private function writeData(string $data): void
    {
        NotConnected::assertConnection($this->connection);

        $this->connection->write($data . "\r\n");
    }

    /**
     * Read SMTP response from server using SmtpResponseFactory.
     *
     * @return SmtpResponse
     *
     * @throws TransportError
     */
    private function readResponse(): SmtpResponse
    {
        NotConnected::assertConnection($this->connection);

        return SmtpResponseFactory::readFromConnection($this->connection);
    }
}
