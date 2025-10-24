<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Validation;

use Larafony\Framework\Tests\Validation\Fixtures\ConditionalRequest;
use Larafony\Framework\Tests\Validation\Fixtures\DataExtractionRequest;
use Larafony\Framework\Tests\Validation\Fixtures\PasswordConfirmationRequest;
use Larafony\Framework\Validation\AttributeValidator;
use Larafony\Framework\Validation\Attributes\Email;
use Larafony\Framework\Validation\Attributes\MinLength;
use Larafony\Framework\Validation\Attributes\Required;
use Larafony\Framework\Validation\Attributes\RequiredWhen;
use Larafony\Framework\Validation\Attributes\ValidWhen;
use PHPUnit\Framework\TestCase;

class AttributeValidatorTest extends TestCase
{
    public function test_validates_object_with_no_errors(): void
    {
        $request = new class {
            #[Required, Email]
            public string $email = 'test@example.com';

            #[Required, MinLength(8)]
            public string $password = 'password123';
        };

        $validator = new AttributeValidator();
        $result = $validator->validate($request);

        $this->assertTrue($result->isValid());
        $this->assertFalse($result->hasErrors());
        $this->assertEmpty($result->errors);
    }

    public function test_collects_validation_errors(): void
    {
        $request = new class {
            #[Required, Email]
            public ?string $email = 'invalid-email';

            #[Required, MinLength(8)]
            public string $password = 'short';
        };

        $validator = new AttributeValidator();
        $result = $validator->validate($request);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrors());
        $this->assertCount(2, $result->errors);

        $fields = array_map(fn($error) => $error->field, $result->errors);
        $this->assertContains('email', $fields);
        $this->assertContains('password', $fields);
    }

    public function test_validates_multiple_rules_per_field(): void
    {
        $request = new class {
            #[Required, Email, MinLength(5)]
            public ?string $email = null;
        };

        $validator = new AttributeValidator();
        $result = $validator->validate($request);

        $this->assertFalse($result->isValid());

        // Should have error from Required (Email won't run on null)
        $this->assertGreaterThanOrEqual(1, count($result->errors));
    }

    public function test_validates_with_conditional_rules(): void
    {
        $request = new ConditionalRequest();
        $request->account_type = 'business';
        $request->company_name = null;

        $validator = new AttributeValidator();
        $result = $validator->validate($request);

        $this->assertFalse($result->isValid());
        $this->assertCount(1, $result->errors);
        $this->assertSame('company_name', $result->errors[0]->field);
    }

    public function test_validates_with_custom_closures(): void
    {
        $request = new PasswordConfirmationRequest();
        $request->password = 'secret123';
        $request->password_confirmation = 'different';

        $validator = new AttributeValidator();
        $result = $validator->validate($request);

        $this->assertFalse($result->isValid());
        $this->assertCount(1, $result->errors);
        $this->assertSame('password_confirmation', $result->errors[0]->field);
        $this->assertSame('Passwords must match', $result->errors[0]->message);
    }

    public function test_passes_field_name_to_validation_rules(): void
    {
        $request = new class {
            public string $password = 'secret123';

            #[Required]
            public string $password_confirmation = 'secret123';
        };

        $validator = new AttributeValidator();
        $result = $validator->validate($request);

        $this->assertTrue($result->isValid());
    }

    public function test_ignores_properties_without_validation_attributes(): void
    {
        $request = new class {
            #[Required]
            public ?string $validated_field = null;

            public string $non_validated_field = 'any value';
            public int $another_field = 123;
        };

        $validator = new AttributeValidator();
        $result = $validator->validate($request);

        // Only validated_field should be checked
        $this->assertCount(1, $result->errors);
        $this->assertSame('validated_field', $result->errors[0]->field);
    }

    public function test_validates_only_public_properties(): void
    {
        $request = new class {
            #[Required]
            public ?string $public_field = null;

            #[Required]
            protected ?string $protected_field = null;

            #[Required]
            private ?string $private_field = null;
        };

        $validator = new AttributeValidator();
        $result = $validator->validate($request);

        // Only public_field should be validated
        $this->assertCount(1, $result->errors);
        $this->assertSame('public_field', $result->errors[0]->field);
    }

    public function test_extracts_data_from_object_properties(): void
    {
        $request = new DataExtractionRequest();
        $request->field1 = 'value1';
        $request->field2 = 'value2';
        $request->field3 = 'value3';

        $validator = new AttributeValidator();
        $result = $validator->validate($request);

        $this->assertTrue($result->isValid());
    }
}
