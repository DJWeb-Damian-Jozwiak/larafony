<?php

declare(strict_types=1);

namespace Larafony\Framework\Validation;

use Larafony\Framework\Exceptions\Validation\ValidationFailed;
use Larafony\Framework\Http\ServerRequest;
use Larafony\Framework\Validation\Attributes\IsValidated;
use ReflectionProperty;

abstract class FormRequest extends ServerRequest
{
    protected ValidationResult $validationResult;

    /**
     * Validate the request using attribute-based validation rules.
     *
     * @throws ValidationFailed
     */
    public function validate(): ValidationResult
    {
        $validator = new AttributeValidator();
        $this->validationResult = $validator->validate($this);

        if (! $this->validationResult->isValid()) {
            throw new ValidationFailed($this->validationResult->errors);
        }

        return $this->validationResult;
    }

    /**
     * Populate properties marked with #[IsValidated] from request data.
     *
     * @return $this
     */
    public function populateProperties(): self
    {
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        $properties = array_filter($properties, $this->hasIsValidatedAttribute(...));
        $properties = array_filter($properties, $this->propertyProvided(...));
        $properties = array_filter($properties, $this->propertyHasType(...));
        $data = $this->baseData();

        foreach ($properties as $property) {
            $value = $data[$property->getName()];
            $propertyName = $property->getName();
            $this->{$propertyName} = $value;
        }

        return $this;
    }

    /**
     * Get all request data (query + body).
     *
     * @return array<string, mixed>
     */
    private function baseData(): array
    {
        return [
            ...$this->getQueryParams(),
            ...(is_array($this->getParsedBody()) ? $this->getParsedBody() : []),
        ];
    }

    private function propertyHasType(ReflectionProperty $property): bool
    {
        $type = $property->getType();
        // Accept both named types (string, int, etc.) and union types (string|null, string|array|null, etc.)
        return $type instanceof \ReflectionNamedType || $type instanceof \ReflectionUnionType;
    }

    private function hasIsValidatedAttribute(\ReflectionProperty $property): bool
    {
        $attributes = $property->getAttributes(IsValidated::class);
        return (bool) ($attributes);
    }

    private function propertyProvided(\ReflectionProperty $property): bool
    {
        $property_name = $property->getName();
        return isset($this->baseData()[$property_name]);
    }
}
