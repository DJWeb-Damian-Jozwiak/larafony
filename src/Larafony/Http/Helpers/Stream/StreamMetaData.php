<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Helpers\Stream;

use ReflectionClass;

final class StreamMetaData
{
    public bool $writeable {
        get => str_contains($this->mode, 'w');
    }
    public bool $readable {
        get => str_contains($this->mode, 'r') || $this->writeable;
    }

    public function __construct(
        public readonly bool $timed_out = false,
        public readonly bool $blocked = false,
        public readonly bool $eof = false,
        public readonly mixed $wrapper_data = [],
        public readonly string $wrapper_type = '',
        public readonly string $stream_type = '',
        public readonly string $uri = '',
        public readonly string $mode = 'r',
        public readonly bool $seekable = false,
        public readonly int $unread_bytes = 0,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $properties = new ReflectionClass($this)->getProperties();
        $data = array_map(fn ($p) => [$p->getName() => $p->getValue($this)], $properties);
        return array_merge(...$data);
    }

    public function get(string $name): mixed
    {
        return $this->toArray()[$name] ?? null;
    }
}
