<?php

declare(strict_types=1);

namespace Larafony\Framework\Validation\Attributes;

use Larafony\Framework\Validation\Contracts\ValidationRule;

abstract class ValidationAttribute implements ValidationRule
{
    public protected(set) string $message;
    /**
     * @var array<string, mixed>
     */
    protected private(set) array $data;
    protected private(set) string $fieldName = '';

    /**
     * @param array<string, mixed> $data
     *
     * @return $this
     */
    public function withData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Set the field name being validated.
     *
     * @param string $fieldName
     *
     * @return $this
     */
    public function withFieldName(string $fieldName): static
    {
        $this->fieldName = $fieldName;
        return $this;
    }
}
