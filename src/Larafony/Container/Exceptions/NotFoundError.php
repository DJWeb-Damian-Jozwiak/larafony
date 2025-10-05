<?php

declare(strict_types=1);

namespace Larafony\Framework\Container\Exceptions;

use Larafony\Framework\Core\Exceptions\NotFoundError as CoreNotFoundError;
use Psr\Container\NotFoundExceptionInterface;

class NotFoundError extends CoreNotFoundError implements NotFoundExceptionInterface
{
}
