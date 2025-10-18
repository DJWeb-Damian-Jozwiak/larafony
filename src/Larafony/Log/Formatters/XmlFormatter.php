<?php

declare(strict_types=1);

namespace Larafony\Framework\Log\Formatters;

use Dom\XMLDocument;
use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Log\Contracts\FormatterContract;
use Larafony\Framework\Log\Message;

final readonly class XmlFormatter implements FormatterContract
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
            'context' => $message->context->all(),
            'metadata' => $message->metadata?->toArray(),
        ];
    }

    public function format(Message $message): string
    {
        $dom = XMLDocument::createEmpty();
        $root = $dom->createElement('log');
        $dom->appendChild($root);

        $this->arrayToXml($this->toArray($message), $root, $dom);

        $dom->formatOutput = true;
        return $dom->saveXml();
    }

    /**
     * @param array<string, mixed> $data
     * @param \Dom\Element $parent
     * @param XMLDocument $dom
     *
     * @return void
     */
    private function arrayToXml(array $data, \Dom\Element $parent, XMLDocument $dom): void
    {
        foreach ($data as $key => $value) {
            $element = $dom->createElement((string) $key);
            if (is_array($value)) {
                $parent->appendChild($element);
                $this->arrayToXml($value, $element, $dom);
            } else {
                $element->textContent = (string) $value;
                $parent->appendChild($element);
            }
        }
    }
}
