<?php

declare(strict_types=1);

namespace Larafony\Framework\Log;

final class PlaceholderProcessor
{
    public function process(string $message, Context $context): ?string
    {
        return preg_replace_callback(
            '/{([^}]+)}/',
            fn (array $matches) => $this->replacePlaceholder($matches[1], $context),
            $message
        );
    }

    private function replacePlaceholder(string $placeholder, Context $context): string
    {
        if (! $context->has($placeholder)) {
            return '{' . $placeholder . '}';
        }

        $value = $context->get($placeholder);

        return match (true) {
            is_string($value), is_numeric($value) => (string) $value,
            is_bool($value) => $value ? 'true' : 'false',
            is_array($value) => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            $value instanceof \Stringable => (string) $value,
            default => '[unserializable]'
        };
    }
}
