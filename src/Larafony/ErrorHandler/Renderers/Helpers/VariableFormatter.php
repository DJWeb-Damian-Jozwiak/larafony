<?php

declare(strict_types=1);

namespace Larafony\Framework\ErrorHandler\Renderers\Helpers;

readonly class VariableFormatter
{
    public function format(mixed $var): string
    {
        return match (true) {
            is_object($var) => sprintf('object(%s)#%d', $var::class, spl_object_id($var)),
            is_array($var) => sprintf('array(%d)', count($var)),
            is_string($var) => sprintf('"%s"', $var),
            is_bool($var) => $var ? 'true' : 'false',
            is_null($var) => 'null',
            default => (string) $var
        };
    }

    /**
     * @param array<int|string, mixed> $args
     */
    public function formatArgs(array $args): string
    {
        return implode(', ', array_map(
            fn ($arg) => $this->format($arg),
            $args
        ));
    }
}
