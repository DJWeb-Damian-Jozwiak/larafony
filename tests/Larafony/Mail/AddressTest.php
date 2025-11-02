<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Mail;

use Larafony\Framework\Mail\Address;
use PHPUnit\Framework\TestCase;

class AddressTest extends TestCase
{
    public function testCanCreateAddressWithEmailOnly(): void
    {
        $address = new Address('test@example.com');

        $this->assertSame('test@example.com', $address->email);
        $this->assertNull($address->name);
    }

    public function testCanCreateAddressWithEmailAndName(): void
    {
        $address = new Address('test@example.com', 'Test User');

        $this->assertSame('test@example.com', $address->email);
        $this->assertSame('Test User', $address->name);
    }

    public function testToStringWithEmailOnly(): void
    {
        $address = new Address('test@example.com');

        $this->assertSame('test@example.com', $address->toString());
        $this->assertSame('test@example.com', (string) $address);
    }

    public function testToStringWithEmailAndName(): void
    {
        $address = new Address('test@example.com', 'Test User');

        $this->assertSame('"Test User" <test@example.com>', $address->toString());
        $this->assertSame('"Test User" <test@example.com>', (string) $address);
    }
}
