<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Validation;

use Larafony\Framework\Validation\ValidationError;
use Larafony\Framework\Validation\ValidationResult;
use PHPUnit\Framework\TestCase;

class ValidationResultTest extends TestCase
{
    public function testCanCreateEmptyValidationResult(): void
    {
        $result = new ValidationResult();

        $this->assertTrue($result->isValid());
        $this->assertFalse($result->hasErrors());
        $this->assertEmpty($result->errors);
    }

    public function testCanAddError(): void
    {
        $result = new ValidationResult();
        $result->addError('email', 'Invalid email format');

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrors());
        $this->assertCount(1, $result->errors);

        $error = $result->errors[0];
        $this->assertInstanceOf(ValidationError::class, $error);
        $this->assertSame('email', $error->field);
        $this->assertSame('Invalid email format', $error->message);
    }

    public function testCanAddMultipleErrors(): void
    {
        $result = new ValidationResult();
        $result->addError('email', 'Invalid email format');
        $result->addError('password', 'Password too short');
        $result->addError('email', 'Email already taken');

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrors());
        $this->assertCount(3, $result->errors);
    }

    public function testErrorsPropertyIsReadonly(): void
    {
        $result = new ValidationResult();

        $this->expectException(\Error::class);
        $result->errors = [];
    }
}
