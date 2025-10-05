<?php

declare(strict_types=1);

namespace Larafony\Framework\Http;

use Larafony\Framework\Http\Helpers\Request\HeaderManager;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

class Message implements MessageInterface
{
    public function __construct(
        protected string $protocolVersion = '1.1',
        protected HeaderManager $headerManager = new HeaderManager(),
        protected ?StreamInterface $body = null,
    ) {
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $version): static
    {
        return clone($this, ['protocolVersion' => $version]);
    }

    /**
     * @return array<string, array<string>>
     */
    public function getHeaders(): array
    {
        return $this->headerManager->getHeaders();
    }

    public function hasHeader(string $name): bool
    {
        return $this->headerManager->hasHeader($name);
    }

    /**
     * @return array<string>
     */
    public function getHeader(string $name): array
    {
        return $this->headerManager->getHeader($name);
    }

    public function getHeaderLine(string $name): string
    {
        return $this->headerManager->getHeaderLine($name);
    }

    public function withHeader(string $name, $value): static
    {
        return clone($this, ['headerManager' => $this->headerManager->withHeader($name, $value)]);
    }

    public function withAddedHeader(string $name, $value): static
    {
        return clone($this, ['headerManager' => $this->headerManager->withAddedHeader($name, $value)]);
    }

    public function withoutHeader(string $name): static
    {
        return clone($this, ['headerManager' => $this->headerManager->withoutHeader($name)]);
    }

    public function getBody(): StreamInterface
    {
        return $this->body ?? throw new \RuntimeException('Body stream is not set');
    }

    public function withBody(StreamInterface $body): static
    {
        return clone($this, ['body' => $body]);
    }
}
