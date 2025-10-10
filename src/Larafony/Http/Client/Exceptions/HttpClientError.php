<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Client\Exceptions;

use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;

/**
 * Base exception for all HTTP client errors.
 *
 * All HTTP client-related errors MUST extend this class,
 * ensuring PSR-18 compliance through ClientExceptionInterface.
 */
class HttpClientError extends RuntimeException implements ClientExceptionInterface
{
}
