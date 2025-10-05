<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Factories;

use Larafony\Framework\Http\Helpers\Response\StatusCodeFactory;
use Larafony\Framework\Http\Response;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

final readonly class ResponseFactory implements ResponseFactoryInterface
{
    public function __construct(
        private StreamFactory $streamFactory = new StreamFactory(),
    ) {
    }

    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        $reason = $reasonPhrase !== '' ? $reasonPhrase : StatusCodeFactory::getReasonPhraseForCode($code);

        return new Response(
            body: $this->streamFactory->createStream(),
            statusCode: $code,
            reasonPhrase: $reason,
        );
    }
}
