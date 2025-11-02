<?php

namespace Larafony\Framework\ErrorHandler;


readonly class TraceFrame
{
    /**
     * @param string $file
     * @param int $line
     * @param string|null $class
     * @param string $function
     * @param array<int, mixed> $args
     * @param CodeSnippet $snippet
     */
    public function __construct(
        public string $file,
        public int $line,
        public ?string $class,
        public string $function,
        public array $args,
        public CodeSnippet $snippet
    )
    {
    }

    /**
     * @param array<string, mixed> $frame
     *
     * @return self
     */
    public static function fromArray(array $frame): self
    {
        return new self(
            file: $frame['file'] ?? '',
            line: $frame['line'] ?? 0,
            class: $frame['class'] ?? null,
            function: $frame['function'],
            args: $frame['args'] ?? [],
            snippet: new CodeSnippet(
                $frame['file'] ?? '',
                $frame['line'] ?? 0
            )
        );
    }
}
