# Chapter 16: Attribute-Based Form Validation

> Part of the Larafony Framework - A modern PHP 8.5 framework built from scratch
>
> 📚 Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)

## Overview

This chapter introduces a powerful, attribute-based validation system that leverages PHP 8.5's advanced features including closures in attributes and first-class callables. Unlike traditional frameworks that rely on configuration arrays or separate validation classes, Larafony implements validation through native PHP attributes attached directly to property declarations.

The implementation follows the Open-Closed Principle by extending the routing system with `FormRequestAwareHandler` without modifying existing code. Form requests automatically validate themselves before reaching controller methods, providing a clean separation of concerns and eliminating boilerplate validation code.

The validation system uses reflection and attribute inspection to discover validation rules at runtime, supporting both simple constraints (Required, Email, Min/Max) and advanced conditional logic using closures (RequiredWhen, RequiredUnless, ValidWhen). All validation logic is kept at complexity ≤5 through the facade pattern and functional programming techniques.

## Key Components

### Core Validation Classes

- **FormRequest** - Abstract base class extending PSR-7 ServerRequest with automatic validation and property population using #[IsValidated] attribute
- **AttributeValidator** - Validates objects by inspecting public properties for validation attributes, using array_filter for complexity reduction
- **FormRequestAwareHandler** - PSR-15 RequestHandler facade that detects FormRequest type-hints and auto-validates (uses FormRequestTypeDetector, FormRequestFactory)
- **ValidationResult** - Immutable result object with `errors` array (using private(set) property hook) and validation state

### Validation Attributes (13 total)

**Basic Constraints:**
- `#[Required]` - Field must not be null
- `#[Email]` - Validates email format using filter_var
- `#[Min(value)]` / `#[Max(value)]` - Numeric range validation
- `#[MinLength(length)]` / `#[MaxLength(length)]` / `#[Length(min, max)]` - String length validation
- `#[StartsWith(prefix)]` / `#[EndsWith(suffix)]` - String pattern matching

**Advanced Conditional Validation:**
- `#[RequiredWhen(Closure)]` - Field required when closure returns true (PHP 8.5 closures in attributes)
- `#[RequiredUnless(Closure)]` - Field required unless closure returns true
- `#[ValidWhen(Closure, message)]` - Custom validation logic with closures
- `#[Confirmed(field)]` - Field confirmation matching (e.g., password_confirmation)

**Marker Attribute:**
- `#[IsValidated]` - Marks properties for auto-population from request data

### Helper Classes (Complexity ≤5)

- **FormRequestFactory** - Creates FormRequest instances using ServerRequestFactory infrastructure, copies properties via reflection with array_walk (not foreach for complexity reduction)
- **FormRequestTypeDetector** - Reflection-based detection of FormRequest type-hints in controller methods
- **ValidationAttribute** - Abstract base class with `withData()` and `withFieldName()` fluent interface for all validation rules

### Exceptions

- **ValidationFailed** - Thrown when validation fails (HTTP 422), contains ValidationError array with `getErrorsArray()` method

## PSR Standards Implemented

- **PSR-7**: HTTP Messages - FormRequest extends ServerRequest, maintaining full PSR-7 compatibility
- **PSR-15**: HTTP Server Request Handlers - FormRequestAwareHandler implements RequestHandlerInterface

## New Attributes

- `#[Required]` - Validates that value is not null
- `#[Email]` - Validates email format
- `#[Min(10)]` / `#[Max(100)]` - Numeric bounds
- `#[MinLength(3)]` / `#[MaxLength(255)]` / `#[Length(3, 255)]` - String length constraints
- `#[StartsWith('https://')]` / `#[EndsWith('.com')]` - String pattern matching
- `#[RequiredWhen(fn($data) => $data['type'] === 'business')]` - Conditional required with closures
- `#[RequiredUnless(fn($data) => !empty($data['phone']))]` - Inverse conditional
- `#[ValidWhen(fn($value, $data) => $value === $data['password'], 'Must match')]` - Custom validation
- `#[Confirmed]` - Field confirmation (looks for `{field}_confirmation`)
- `#[IsValidated]` - Marker for auto-population from request data

## Usage Examples

### Basic Example

```php
<?php

use Larafony\Framework\Validation\FormRequest;
use Larafony\Framework\Validation\Attributes\{Required, Email, MinLength, IsValidated};

class RegisterRequest extends FormRequest
{
    #[IsValidated]
    #[Required]
    #[Email]
    public ?string $email = null;

    #[IsValidated]
    #[Required]
    #[MinLength(8)]
    public ?string $password = null;
}

// In controller
class UserController
{
    public function register(RegisterRequest $request): ResponseInterface
    {
        // Request is automatically validated and properties populated
        // If validation fails, ValidationFailed exception is thrown (HTTP 422)

        $user = User::create([
            'email' => $request->email,
            'password' => password_hash($request->password, PASSWORD_DEFAULT),
        ]);

        return new JsonResponse(['id' => $user->id], 201);
    }
}
```

### Advanced Example with Closures

```php
<?php

use Larafony\Framework\Validation\Attributes\{
    Required, Email, RequiredWhen, RequiredUnless, ValidWhen, Confirmed, IsValidated
};

class BusinessRegistrationRequest extends FormRequest
{
    #[IsValidated]
    #[Required]
    public ?string $type = null; // 'personal' or 'business'

    #[IsValidated]
    #[Required]
    #[Email]
    public ?string $email = null;

    // Required only for business accounts
    #[IsValidated]
    #[RequiredWhen(fn(array $data) => $data['type'] === 'business')]
    public ?string $companyName = null;

    #[IsValidated]
    #[RequiredWhen(fn(array $data) => $data['type'] === 'business')]
    public ?string $taxId = null;

    // Required unless phone is provided
    #[IsValidated]
    #[RequiredUnless(fn(array $data) => !empty($data['phone']))]
    public ?string $alternativeContact = null;

    // Password with confirmation
    #[IsValidated]
    #[Required]
    #[MinLength(8)]
    #[Confirmed]
    public ?string $password = null;

    #[IsValidated]
    public ?string $password_confirmation = null;

    // Custom validation: age must be 18+ if type is business
    #[IsValidated]
    #[ValidWhen(
        fn(mixed $value, array $data) =>
            $data['type'] !== 'business' || ($value !== null && $value >= 18),
        message: 'Must be 18+ for business accounts'
    )]
    public ?int $age = null;
}
```

### Using First-Class Callables (PHP 8.5)

```php
<?php

class InvoiceRequest extends FormRequest
{
    #[IsValidated]
    #[Required]
    public ?string $invoiceType = null; // 'standard' or 'proforma'

    // Using first-class callable syntax
    #[IsValidated]
    #[RequiredWhen(self::isStandardInvoice(...))]
    public ?string $paymentMethod = null;

    #[IsValidated]
    #[ValidWhen(self::validInvoiceNumber(...), 'Invalid invoice format')]
    public ?string $invoiceNumber = null;

    private static function isStandardInvoice(array $data): bool
    {
        return $data['invoiceType'] === 'standard';
    }

    private static function validInvoiceNumber(mixed $value, array $data): bool
    {
        if ($data['invoiceType'] === 'standard') {
            return preg_match('/^INV-\d{4}-\d{4}$/', $value) === 1;
        }

        return preg_match('/^PRO-\d{4}-\d{4}$/', $value) === 1;
    }
}
```

## Comparison with Other Frameworks

| Feature | Larafony                                            | Spatie Laravel Data | Laravel | Symfony |
|---------|-----------------------------------------------------|---------------------|---------|---------|
| **Validation Approach** | PHP 8.5 Attributes on properties                    | PHP 8.1+ Attributes on Data objects | Array-based rules in methods | Annotation/Attribute constraints |
| **Configuration** | Native attributes, zero config                      | Attributes + Laravel validation | `rules()` method returns array | YAML/XML/PHP/Annotations |
| **PHP Version** | PHP 8.5+  | PHP 8.1+ (production safe) | PHP 8.1+ | PHP 8.1+ |
| **Closures in Validation** | ✅ Full closure support in attributes (PHP 8.5)      | ❌ Uses Laravel Rule objects | ❌ Only Rule objects, no closures in rules | ❌ Expression language only |
| **First-Class Callables** | ✅ `self::method(...)` syntax supported              | ❌ Not available (PHP 8.1) | ❌ Not available | ❌ Not available |
| **Conditional Validation** | `RequiredWhen(Closure)` / `RequiredUnless(Closure)` | `#[RequiredIf]` with field references | `required_if:field,value` string syntax | `Expression` constraint with limited syntax |
| **Custom Validation** | `ValidWhen(Closure, message)` inline                | `#[Rule]` attribute with Laravel rules | `Rule::when()` or custom Rule classes | Custom Constraint + Validator classes |
| **Auto-Population** | `#[IsValidated]` marker attribute                   | Automatic via Data object | Manual `validated()` call | FormType + DataMapper |
| **Type Hints** | FormRequest extends PSR-7 ServerRequest             | Data objects (separate from Request) | FormRequest extends Illuminate\Http\FormRequest | No form request concept |
| **PSR Compliance** | PSR-7, PSR-15                                       | Depends on Laravel (custom) | Custom implementations | PSR-7 optional (HttpFoundation default) |
| **Error Response** | PSR-7 ResponseInterface                             | Laravel validation responses | JSON/redirect based on request type | Form errors bound to form |
| **Complexity** | ≤5 (facade pattern, array_filter)                   | Medium (wraps Laravel validation) | Higher (nested method calls) | Higher (builder pattern, event system) |
| **Integration** | Standalone, framework-agnostic                      | Laravel-specific package | Laravel core | Symfony core |

**Key Differences:**

1. **Inspiration vs Innovation**: Larafony was inspired by **Spatie Laravel Data** but pushes boundaries with PHP 8.5 features that production packages can't use yet. Spatie stays production-safe with PHP 8.1+

2. **Closure Power**: Larafony's `RequiredWhen(fn($data) => ...)` allows arbitrary PHP logic in attributes using PHP 8.5's closures-in-attributes feature. Spatie/Laravel require Rule objects, Symfony uses limited Expression language

3. **First-Class Callables**: Larafony supports `#[RequiredWhen(self::method(...))]` syntax (PHP 8.5), providing clean method references without anonymous functions. Not available in PHP 8.1 (Spatie/Laravel/Symfony)

4. **PSR Standards**: FormRequest extends PSR-7 ServerRequest, maintaining full compatibility with PSR-15 middleware. Laravel/Spatie use framework-specific implementations, Symfony uses HttpFoundation

5. **Zero Configuration**: Validation rules declared at property level with no separate configuration. Spatie also uses attributes but wraps Laravel's validation system underneath

6. **Production-Ready vs Cutting-Edge**: Spatie Laravel Data is battle-tested in production with PHP 8.1+. Larafony explores PHP 8.5 features that production packages won't dare use for years

7. **Functional Approach**: Uses `array_filter`, `array_walk`, and `array_any` to reduce cyclomatic complexity below 5, avoiding explicit loops

8. **Open-Closed Principle**: `FormRequestAwareHandler` adds validation without modifying existing routing code

---

📚 **Learn More:** This implementation is explained in detail with step-by-step tutorials, tests, and best practices at [masterphp.eu](https://masterphp.eu)
