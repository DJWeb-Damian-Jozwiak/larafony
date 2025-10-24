<?php

declare(strict_types=1);

namespace Larafony\Framework\Validation\Helpers;

use Larafony\Framework\Http\Factories\ServerRequestFactory;
use Larafony\Framework\Validation\FormRequest;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionProperty;

/**
 * Creates FormRequest instance from ServerRequest using ServerRequestFactory.
 *
 * Uses existing HTTP factory infrastructure to ensure proper initialization.
 *
 * Complexity: 5
 */
readonly class FormRequestFactory
{
    public function __construct(
        private ServerRequestFactory $serverRequestFactory,
    ) {
    }

    /**
     * Create FormRequest from ServerRequest by copying all data.
     *
     * @param class-string<FormRequest> $formRequestClass
     */
    public function create(string $formRequestClass, ServerRequestInterface $source): FormRequest
    {
        // Create base request using factory
        $baseRequest = $this->serverRequestFactory->createServerRequest(
            $source->getMethod(),
            $source->getUri(),
            $source->getServerParams()
        );

        // Instantiate FormRequest using reflection to bypass constructor
        $reflection = new \ReflectionClass($formRequestClass);
        /** @var FormRequest $formRequest */
        $formRequest = $reflection->newInstanceWithoutConstructor();

        // Copy all properties from base request to form request
        $this->copyProperties($baseRequest, $formRequest);

        // Apply immutable modifications
        return $this->copyRequestData($formRequest, $source);
    }

    /**
     * Copy protected properties from base request to form request.
     */
    private function copyProperties(ServerRequestInterface $base, FormRequest $target): void
    {
        $baseReflection = new \ReflectionClass($base);
        $props = $baseReflection->getProperties();
        array_walk(
            $props,
            function (ReflectionProperty $property) use ($target, $base): void {
                $this->copyProperty($property, $base, $target);
            }
        );
    }

    /**
     * Copy single property from base to target if it exists.
     */
    private function copyProperty(
        \ReflectionProperty $property,
        ServerRequestInterface $base,
        FormRequest $target
    ): void {
        $propertyName = $property->getName();

        // Only copy if property exists in target
        if (property_exists($target, $propertyName)) {
            $value = $property->getValue($base);
            $targetProperty = new \ReflectionProperty($target, $propertyName);
            $targetProperty->setValue($target, $value);
        }
    }

    /**
     * Copy all data from source request using immutable methods.
     */
    private function copyRequestData(FormRequest $formRequest, ServerRequestInterface $source): FormRequest
    {
        $formRequest = $formRequest
            ->withQueryParams($source->getQueryParams())
            ->withParsedBody($this->extractParsedBodyArray($source))
            ->withUploadedFiles($source->getUploadedFiles())
            ->withBody($source->getBody());

        $formRequest = $this->copyHeaders($formRequest, $source);
        return $this->copyAttributes($formRequest, $source);
    }

    /**
     * Extract parsed body as array or null.
     *
     * @return array<string, mixed>|null
     */
    private function extractParsedBodyArray(ServerRequestInterface $source): ?array
    {
        $parsedBody = $source->getParsedBody();

        return is_array($parsedBody) ? $parsedBody : null;
    }

    /**
     * Copy headers from source to target request.
     */
    private function copyHeaders(FormRequest $formRequest, ServerRequestInterface $source): FormRequest
    {
        foreach ($source->getHeaders() as $name => $values) {
            $formRequest = $formRequest->withHeader($name, $values);
        }

        return $formRequest;
    }

    /**
     * Copy attributes from source to target request.
     */
    private function copyAttributes(FormRequest $formRequest, ServerRequestInterface $source): FormRequest
    {
        foreach ($source->getAttributes() as $name => $value) {
            $formRequest = $formRequest->withAttribute($name, $value);
        }

        return $formRequest;
    }
}
