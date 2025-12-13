<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Validation;

use Larafony\Framework\Validation\ValidationError;
use PHPUnit\Framework\TestCase;

class ValidationErrorTest extends TestCase
{
    public function testCanCreateValidationError(): void
    {
        $error = new ValidationError('email', 'Invalid email format');

        $this->assertSame('email', $error->field);
        $this->assertSame('Invalid email format', $error->message);
    }

    public function testValidationErrorIsReadonly(): void
    {
        $error = new ValidationError('email', 'Invalid email format');

        $this->expectException(\Error::class);
        $error->field = 'password';
    }
}
