<?php

declare(strict_types=1);

namespace Larafony\Framework\Http;

use Larafony\Framework\Http\Factories\StreamFactory;
use Larafony\Framework\Http\Helpers\Request\HeaderManager;
use Larafony\Framework\Http\Helpers\Response\StatusCodeFactory;

final class JsonResponse extends Response
{
    /**
     * @param array<string, string|array<int, string>> $headers
     */
    public function __construct(
        mixed $data,
        int $statusCode = 200,
        array $headers = [],
        string $protocolVersion = '1.1',
    ) {
        $flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR;
        $json = json_encode($data, $flags);
        $headers['Content-Type'] = 'application/json; charset=utf-8';

        parent::__construct(
            protocolVersion: $protocolVersion,
            headerManager: new HeaderManager($headers),
            body: new StreamFactory()->createStream($json),
            statusCode: $statusCode,
            reasonPhrase: StatusCodeFactory::getReasonPhraseForCode($statusCode),
        );
    }
}
