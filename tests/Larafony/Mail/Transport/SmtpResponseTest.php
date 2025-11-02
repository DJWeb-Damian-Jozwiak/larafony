<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Mail\Transport;

use Larafony\Framework\Mail\Exceptions\TransportError;
use Larafony\Framework\Mail\Transport\SmtpResponse;
use PHPUnit\Framework\TestCase;

class SmtpResponseTest extends TestCase
{
    public function testCanCreateResponse(): void
    {
        $response = new SmtpResponse(220, 'Ready');

        $this->assertSame(220, $response->code);
        $this->assertSame('Ready', $response->message);
    }

    public function testFromStringParsesResponse(): void
    {
        $response = SmtpResponse::fromString('220 smtp.example.com ESMTP ready');

        $this->assertSame(220, $response->code);
        $this->assertSame('smtp.example.com ESMTP ready', $response->message);
    }

    public function testIsSuccessFor2xxCodes(): void
    {
        $response = new SmtpResponse(250, 'OK');

        $this->assertTrue($response->isSuccess);
        $this->assertFalse($response->isError);
    }

    public function testIsSuccessFor3xxCodes(): void
    {
        $response = new SmtpResponse(354, 'Start mail input');

        $this->assertTrue($response->isSuccess);
        $this->assertFalse($response->isError);
    }

    public function testIsErrorFor4xxCodes(): void
    {
        $response = new SmtpResponse(421, 'Service not available');

        $this->assertFalse($response->isSuccess);
        $this->assertTrue($response->isError);
    }

    public function testIsErrorFor5xxCodes(): void
    {
        $response = new SmtpResponse(550, 'Mailbox unavailable');

        $this->assertFalse($response->isSuccess);
        $this->assertTrue($response->isError);
    }

    public function testAssertSuccessDoesNotThrowForSuccessCode(): void
    {
        $response = new SmtpResponse(250, 'OK');

        $response->assertSuccess();

        $this->assertTrue(true);
    }

    public function testAssertSuccessThrowsForErrorCode(): void
    {
        $response = new SmtpResponse(550, 'Mailbox unavailable');

        $this->expectException(TransportError::class);
        $this->expectExceptionMessage('SMTP Error [550]: Mailbox unavailable');

        $response->assertSuccess();
    }
}
