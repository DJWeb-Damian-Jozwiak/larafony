<?php

declare(strict_types=1);

namespace Larafony\Framework\WebSockets\Protocol;

final readonly class Frame
{
    public function __construct(
        public bool $fin,
        public Opcode $opcode,
        public bool $mask,
        public int $payloadLength,
        public ?string $maskingKey,
        public string $payload,
    ) {
    }

    public static function text(string $payload, bool $mask = false): self
    {
        return new self(
            fin: true,
            opcode: Opcode::TEXT,
            mask: $mask,
            payloadLength: strlen($payload),
            maskingKey: $mask ? random_bytes(4) : null,
            payload: $payload,
        );
    }

    public static function binary(string $payload, bool $mask = false): self
    {
        return new self(
            fin: true,
            opcode: Opcode::BINARY,
            mask: $mask,
            payloadLength: strlen($payload),
            maskingKey: $mask ? random_bytes(4) : null,
            payload: $payload,
        );
    }

    public static function ping(string $payload = ''): self
    {
        return new self(
            fin: true,
            opcode: Opcode::PING,
            mask: false,
            payloadLength: strlen($payload),
            maskingKey: null,
            payload: $payload,
        );
    }

    public static function pong(string $payload = ''): self
    {
        return new self(
            fin: true,
            opcode: Opcode::PONG,
            mask: false,
            payloadLength: strlen($payload),
            maskingKey: null,
            payload: $payload,
        );
    }

    public static function close(int $code = 1000, string $reason = ''): self
    {
        $payload = pack('n', $code) . $reason;

        return new self(
            fin: true,
            opcode: Opcode::CLOSE,
            mask: false,
            payloadLength: strlen($payload),
            maskingKey: null,
            payload: $payload,
        );
    }
}
