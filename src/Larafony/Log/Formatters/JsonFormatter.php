<?php

declare(strict_types=1);

namespace Larafony\Framework\Log\Formatters;

use Larafony\Framework\Log\Contracts\FormatterContract;
use Larafony\Framework\Log\Message;

final readonly class JsonFormatter implements FormatterContract
{
    public function format(Message $message): string
    {
        return json_encode(
            $this->toArray($message),
            JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
        );
    }

    /**
     * @param Message $message
     *
     * @return array<string, mixed>
     */
    public function toArray(Message $message): array
    {
        $level = $message->level->name ?? $message->level;
        return array_filter(
            [
                'level' => $level,
                'message' => $message->message,
                'context' => $message->context->all(),
                'metadata' => $message->metadata?->toArray(),
            ]
        );
    }
}
