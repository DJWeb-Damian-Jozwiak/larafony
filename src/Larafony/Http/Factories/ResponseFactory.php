<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Factories;

use Larafony\Framework\Http\Helpers\Response\StatusCodeFactory;
use Larafony\Framework\Http\JsonResponse;
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

    /**
     * Create a JSON response with automatic content-type header
     *
     * @param mixed $data Data to be JSON encoded
     * @param int $statusCode HTTP status code
     * @param array<string, string|array<int, string>> $headers Additional headers
     *
     * @return JsonResponse
     */
    public function createJsonResponse(
        mixed $data,
        int $statusCode = 200,
        array $headers = []
    ): JsonResponse {
        return new JsonResponse($data, $statusCode, $headers)->withStatus($statusCode);
    }
}
