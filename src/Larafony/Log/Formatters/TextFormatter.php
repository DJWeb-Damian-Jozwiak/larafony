<?php

declare(strict_types=1);

namespace Larafony\Framework\Log\Formatters;

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Log\Contracts\FormatterContract;
use Larafony\Framework\Log\Message;

final readonly class TextFormatter implements FormatterContract
{
    /**
     * @param Message $message
     *
     * @return array<string, mixed>
     */
    public function toArray(Message $message): array
    {
        $level = $message->level->name ?? $message->level;
        return [
            'datetime' => ClockFactory::now()->format('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message->message,
            'context' => json_encode($message->context->all()),
            'metadata' => json_encode($message->metadata?->toArray() ?? []),
        ];
    }

    public function format(Message $message): string
    {
        $data = $this->toArray($message);
        return sprintf(
            "[%s] %s: %s Context: %s Metadata: %s\n",
            $data['datetime'],
            $data['level'],
            $data['message'],
            $data['context'],
            $data['metadata']
        );
    }
}
