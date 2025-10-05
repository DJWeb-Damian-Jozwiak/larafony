<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Helpers\Response;

final readonly class StatusCodeFactory
{
    public static function tryFromCode(int $code): ?StatusCode
    {
        return StatusCode::tryFrom($code);
    }

    public static function getReasonPhraseForCode(int $code): string
    {
        $statusCode = self::tryFromCode($code);

        return $statusCode ? ReasonPhraseMapper::getReasonPhrase($statusCode) : '';
    }
}
