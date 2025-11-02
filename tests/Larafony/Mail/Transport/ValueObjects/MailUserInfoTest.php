<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Mail\Transport\ValueObjects;

use Larafony\Framework\Mail\Transport\ValueObjects\MailUserInfo;
use PHPUnit\Framework\TestCase;

class MailUserInfoTest extends TestCase
{
    public function testCanCreateEmptyUserInfo(): void
    {
        $userInfo = new MailUserInfo();

        $this->assertNull($userInfo->username);
        $this->assertNull($userInfo->password);
        $this->assertFalse($userInfo->hasCredentials);
    }

    public function testCanCreateWithUsernameOnly(): void
    {
        $userInfo = new MailUserInfo('testuser');

        $this->assertSame('testuser', $userInfo->username);
        $this->assertNull($userInfo->password);
        $this->assertTrue($userInfo->hasCredentials);
    }

    public function testCanCreateWithUsernameAndPassword(): void
    {
        $userInfo = new MailUserInfo('testuser', 'testpass');

        $this->assertSame('testuser', $userInfo->username);
        $this->assertSame('testpass', $userInfo->password);
        $this->assertTrue($userInfo->hasCredentials);
    }

    public function testFromStringWithUsernameOnly(): void
    {
        $userInfo = MailUserInfo::fromString('testuser');

        $this->assertSame('testuser', $userInfo->username);
        $this->assertNull($userInfo->password);
    }

    public function testFromStringWithUsernameAndPassword(): void
    {
        $userInfo = MailUserInfo::fromString('testuser:testpass');

        $this->assertSame('testuser', $userInfo->username);
        $this->assertSame('testpass', $userInfo->password);
    }

    public function testFromStringWithPasswordContainingColon(): void
    {
        $userInfo = MailUserInfo::fromString('testuser:pass:word:123');

        $this->assertSame('testuser', $userInfo->username);
        $this->assertSame('pass:word:123', $userInfo->password);
    }
}
