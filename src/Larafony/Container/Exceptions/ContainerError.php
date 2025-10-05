<?php

declare(strict_types=1);

namespace Larafony\Framework\Container\Exceptions;

use Psr\Container\ContainerExceptionInterface;

class ContainerError extends \RuntimeException implements ContainerExceptionInterface
{
}