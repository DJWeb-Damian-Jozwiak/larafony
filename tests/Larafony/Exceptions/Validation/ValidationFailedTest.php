<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Exceptions\Validation;

use Larafony\Framework\Exceptions\Validation\ValidationFailed;
use Larafony\Framework\Validation\ValidationError;
use PHPUnit\Framework\TestCase;

class ValidationFailedTest extends TestCase
{
    public function test_can_create_validation_exception(): void
    {
        $errors = [
            new ValidationError('email', 'Invalid email'),
            new ValidationError('password', 'Too short'),
        ];

        $exception = new ValidationFailed($errors);

        $this->assertSame(422, $exception->getCode());
        $this->assertSame('Validation failed', $exception->getMessage());
        $this->assertSame($errors, $exception->errors);
    }

    public function test_can_use_custom_message(): void
    {
        $errors = [new ValidationError('email', 'Invalid')];
        $exception = new ValidationFailed($errors, 'Custom validation message');

        $this->assertSame('Custom validation message', $exception->getMessage());
    }

    public function test_can_use_custom_code(): void
    {
        $errors = [new ValidationError('email', 'Invalid')];
        $exception = new ValidationFailed($errors, 'Message', 400);

        $this->assertSame(400, $exception->getCode());
    }

    public function test_gets_errors_as_array(): void
    {
        $errors = [
            new ValidationError('email', 'Invalid email'),
            new ValidationError('password', 'Too short'),
            new ValidationError('email', 'Already taken'),
        ];

        $exception = new ValidationFailed($errors);
        $errorsArray = $exception->getErrorsArray();

        $this->assertArrayHasKey('email', $errorsArray);
        $this->assertArrayHasKey('password', $errorsArray);

        $this->assertCount(2, $errorsArray['email']);
        $this->assertContains('Invalid email', $errorsArray['email']);
        $this->assertContains('Already taken', $errorsArray['email']);

        $this->assertCount(1, $errorsArray['password']);
        $this->assertContains('Too short', $errorsArray['password']);
    }

    public function test_errors_array_groups_by_field(): void
    {
        $errors = [
            new ValidationError('name', 'Required'),
            new ValidationError('name', 'Too short'),
            new ValidationError('name', 'Invalid characters'),
        ];

        $exception = new ValidationFailed($errors);
        $errorsArray = $exception->getErrorsArray();

        $this->assertCount(1, $errorsArray);
        $this->assertCount(3, $errorsArray['name']);
    }

    public function test_errors_property_is_readonly(): void
    {
        $errors = [new ValidationError('email', 'Invalid')];
        $exception = new ValidationFailed($errors);

        $this->expectException(\Error::class);
        $exception->errors = [];
    }
}
