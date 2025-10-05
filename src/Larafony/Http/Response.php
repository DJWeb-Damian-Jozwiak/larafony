<?php

declare(strict_types=1);

namespace Larafony\Framework\Http;

use Larafony\Framework\Http\Factories\StreamFactory;
use Larafony\Framework\Http\Helpers\Request\HeaderManager;
use Larafony\Framework\Http\Helpers\Response\StatusCodeFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class Response extends Message implements ResponseInterface
{
    public function __construct(
        string $protocolVersion = '1.1',
        HeaderManager $headerManager = new HeaderManager(),
        ?StreamInterface $body = null,
        protected int $statusCode = 200,
        protected ?string $reasonPhrase = null,
    ) {
        parent::__construct(
            protocolVersion: $protocolVersion,
            headerManager: $headerManager,
            body: $body ?? new StreamFactory()->createStream(),
        );

        $this->reasonPhrase ??= StatusCodeFactory::getReasonPhraseForCode($statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): static
    {
        $reason = $reasonPhrase !== ''
            ? $reasonPhrase
            : StatusCodeFactory::getReasonPhraseForCode($code);

        return clone($this, [
            'statusCode' => $code,
            'reasonPhrase' => $reason,
        ]);
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    // Convenience methods

    public function withContent(string $content): static
    {
        return clone($this, ['body' => new StreamFactory()->createStream($content)]);
    }

    /**
     * @param array<int|string, mixed> $data
     */
    public function withJson(array $data, int $status = 200): static
    {
        $json = json_encode($data, JSON_THROW_ON_ERROR);

        return clone($this, [
            'body' => new StreamFactory()->createStream($json),
            'headerManager' => $this->headerManager->withHeader('Content-Type', 'application/json'),
            'statusCode' => $status,
            'reasonPhrase' => StatusCodeFactory::getReasonPhraseForCode($status),
        ]);
    }

    public function redirect(string $url, int $status = 302): static
    {
        return clone($this, [
            'headerManager' => $this->headerManager->withHeader('Location', $url),
            'statusCode' => $status,
            'reasonPhrase' => StatusCodeFactory::getReasonPhraseForCode($status),
        ]);
    }
}
