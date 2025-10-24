<?php

declare(strict_types=1);

namespace Larafony\Framework\Validation\Helpers;

use Larafony\Framework\Validation\FormRequest;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Detects if method parameter is a FormRequest subclass.
 *
 * Complexity: 3
 */
readonly class FormRequestTypeDetector
{
    /**
     * Get FormRequest class name from method's first parameter.
     *
     * @param ReflectionMethod $method
     *
     * @return class-string<FormRequest>|null
     */
    public function detect(ReflectionMethod $method): ?string
    {
        $parameters = $method->getParameters();

        if (count($parameters) === 0) {
            return null;
        }

        $type = $parameters[0]->getType();

        if (! $type instanceof ReflectionNamedType) {
            return null;
        }

        $typeName = $type->getName();

        if (! is_subclass_of($typeName, FormRequest::class)) {
            return null;
        }

        return $typeName;
    }
}
