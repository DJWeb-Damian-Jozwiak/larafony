<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Validation;

use Larafony\Framework\Exceptions\Validation\ValidationFailed;
use Larafony\Framework\Validation\Attributes\Email;
use Larafony\Framework\Validation\Attributes\IsValidated;
use Larafony\Framework\Validation\Attributes\MinLength;
use Larafony\Framework\Validation\Attributes\Required;
use Larafony\Framework\Validation\FormRequest;
use PHPUnit\Framework\TestCase;

class FormRequestTest extends TestCase
{
    public function testFormRequestExtendsServerRequest(): void
    {
        $request = new class extends FormRequest {
        };

        $this->assertInstanceOf(\Psr\Http\Message\ServerRequestInterface::class, $request);
    }

    public function testValidatePassesWithValidData(): void
    {
        $request = new class extends FormRequest {
            #[Required, Email]
            public string $email = 'test@example.com';

            #[Required, MinLength(8)]
            public string $password = 'password123';
        };

        $result = $request->validate();

        $this->assertTrue($result->isValid());
    }

    public function testValidateThrowsExceptionOnInvalidData(): void
    {
        $request = new class extends FormRequest {
            #[Required, Email]
            public ?string $email = null;
        };

        $this->expectException(ValidationFailed::class);
        $this->expectExceptionCode(422);

        $request->validate();
    }

    public function testValidateExceptionContainsErrors(): void
    {
        $request = new class extends FormRequest {
            #[Required]
            public ?string $field1 = null;

            #[Required]
            public ?string $field2 = null;
        };

        try {
            $request->validate();
            $this->fail('ValidationFailed should have been thrown');
        } catch (ValidationFailed $e) {
            $this->assertCount(2, $e->errors);
            $errorsArray = $e->getErrorsArray();
            $this->assertArrayHasKey('field1', $errorsArray);
            $this->assertArrayHasKey('field2', $errorsArray);
        }
    }

    public function testPopulatePropertiesFillsValidatedFields(): void
    {
        $request = new class extends FormRequest {
            #[IsValidated]
            public ?string $email = null;

            #[IsValidated]
            public ?string $password = null;

            public ?string $not_validated = null;
        };

        $request = $request->withParsedBody(['email' => 'test@example.com', 'password' => 'secret']);
        $request->populateProperties();

        $this->assertSame('test@example.com', $request->email);
        $this->assertSame('secret', $request->password);
        $this->assertNull($request->not_validated);
    }

    public function testPopulatePropertiesFromQueryParams(): void
    {
        $request = new class extends FormRequest {
            #[IsValidated]
            public ?string $search = null;
        };

        $request = $request->withQueryParams(['search' => 'test query']);
        $request->populateProperties();

        $this->assertSame('test query', $request->search);
    }

    public function testPopulatePropertiesFromParsedBody(): void
    {
        $request = new class extends FormRequest {
            #[IsValidated]
            public ?string $username = null;
        };

        $request = $request->withParsedBody(['username' => 'johndoe']);
        $request->populateProperties();

        $this->assertSame('johndoe', $request->username);
    }

    public function testPopulatePropertiesBodyOverridesQuery(): void
    {
        $request = new class extends FormRequest {
            #[IsValidated]
            public ?string $value = null;
        };

        $request = $request
            ->withQueryParams(['value' => 'from_query'])
            ->withParsedBody(['value' => 'from_body']);

        $request->populateProperties();

        $this->assertSame('from_body', $request->value);
    }

    public function testPopulatePropertiesOnlyFillsTypedProperties(): void
    {
        $request = new class extends FormRequest {
            #[IsValidated]
            public string $typed;

            #[IsValidated]
            public $untyped;
        };

        $request = $request->withParsedBody([
            'typed' => 'value1',
            'untyped' => 'value2',
        ]);

        $request->populateProperties();

        $this->assertSame('value1', $request->typed);
        $this->assertFalse(isset($request->untyped));
    }

    public function testPopulatePropertiesSkipsMissingFields(): void
    {
        $request = new class extends FormRequest {
            #[IsValidated]
            public ?string $present = null;

            #[IsValidated]
            public ?string $missing = null;
        };

        $request = $request->withParsedBody(['present' => 'value']);
        $request->populateProperties();

        $this->assertSame('value', $request->present);
        $this->assertNull($request->missing);
    }
}
