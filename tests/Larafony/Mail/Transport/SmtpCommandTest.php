<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Mail\Transport;

use Larafony\Framework\Mail\Transport\SmtpCommand;
use PHPUnit\Framework\TestCase;

class SmtpCommandTest extends TestCase
{
    public function testEhloCommand(): void
    {
        $command = SmtpCommand::ehlo('example.com');

        $this->assertSame('EHLO example.com', $command->value);
        $this->assertSame("EHLO example.com\r\n", $command->toString());
    }

    public function testEhloCommandDefaultHostname(): void
    {
        $command = SmtpCommand::ehlo();

        $this->assertSame('EHLO localhost', $command->value);
    }

    public function testAuthLoginCommand(): void
    {
        $command = SmtpCommand::authLogin();

        $this->assertSame('AUTH LOGIN', $command->value);
    }

    public function testUsernameCommand(): void
    {
        $command = SmtpCommand::username('testuser');

        $this->assertSame(base64_encode('testuser'), $command->value);
    }

    public function testPasswordCommand(): void
    {
        $command = SmtpCommand::password('testpass');

        $this->assertSame(base64_encode('testpass'), $command->value);
    }

    public function testMailFromCommand(): void
    {
        $command = SmtpCommand::mailFrom('sender@example.com');

        $this->assertSame('MAIL FROM:<sender@example.com>', $command->value);
    }

    public function testRcptToCommand(): void
    {
        $command = SmtpCommand::rcptTo('recipient@example.com');

        $this->assertSame('RCPT TO:<recipient@example.com>', $command->value);
    }

    public function testDataCommand(): void
    {
        $command = SmtpCommand::data();

        $this->assertSame('DATA', $command->value);
    }

    public function testDataEndCommand(): void
    {
        $command = SmtpCommand::dataEnd();

        $this->assertSame('.', $command->value);
    }

    public function testQuitCommand(): void
    {
        $command = SmtpCommand::quit();

        $this->assertSame('QUIT', $command->value);
    }

    public function testCommandIsStringable(): void
    {
        $command = SmtpCommand::ehlo();

        $this->assertSame("EHLO localhost\r\n", (string) $command);
    }

    public function testCommandValidatesLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SMTP command length must be between 1 and 512 characters');

        // In PHP 8.5, private constructors are accessible via reflection by default
        $reflection = new \ReflectionClass(SmtpCommand::class);
        $constructor = $reflection->getConstructor();
        $instance = $reflection->newInstanceWithoutConstructor();
        $constructor->invoke($instance, str_repeat('A', 513));
    }
}
