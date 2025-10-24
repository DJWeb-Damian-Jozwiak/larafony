<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Validation;

use Larafony\Framework\Validation\ValidationError;
use PHPUnit\Framework\TestCase;

class ValidationErrorTest extends TestCase
{
    public function test_can_create_validation_error(): void
    {
        $error = new ValidationError('email', 'Invalid email format');

        $this->assertSame('email', $error->field);
        $this->assertSame('Invalid email format', $error->message);
    }

    public function test_validation_error_is_readonly(): void
    {
        $error = new ValidationError('email', 'Invalid email format');

        $this->expectException(\Error::class);
        $error->field = 'password';
    }
}
