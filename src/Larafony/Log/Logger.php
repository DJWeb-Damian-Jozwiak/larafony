<?php

declare(strict_types=1);

namespace Larafony\Framework\Log;

use Larafony\Framework\Enums\Log\LogLevel;
use Larafony\Framework\Log\Contracts\HandlerContract;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Stringable;

final class Logger implements LoggerInterface
{
    use LoggerTrait;

    /**
     * @param array<int, HandlerContract> $handlers
     * @param PlaceholderProcessor|null $placeholderProcessor
     */
    public function __construct(
        public private(set) array $handlers = [],
        private ?PlaceholderProcessor $placeholderProcessor = null
    ) {
        /** @phpstan-ignore-next-line */
        $this->handlers = array_filter($this->handlers, static fn ($handler) => $handler instanceof HandlerContract);
        $this->placeholderProcessor ??= new PlaceholderProcessor();
    }

    /**
     * @param LogLevel $level
     * @param Stringable|string $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public function log($level, Stringable|string $message, array $context = []): void
    {
        $message = is_string($message) ? $message : (string) $message;
        $processedMessage = new Message(
            level: $level,
            message: $this->placeholderProcessor?->process($message, new Context($context)) ?? '',
            context: new Context($context),
            metadata: Metadata::create()
        );
        array_walk($this->handlers, static fn (HandlerContract $handler) => $handler->handle($processedMessage));
    }
}
