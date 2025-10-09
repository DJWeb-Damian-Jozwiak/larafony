<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Exceptions;

use Larafony\Framework\Core\Exceptions\NotFoundError;
use Psr\Http\Message\ServerRequestInterface;

class RouteNotFoundError extends NotFoundError
{
    public function __construct(ServerRequestInterface $request)
    {
        $msg = sprintf('Route for %s %s not found', $request->getMethod(), $request->getUri()->getPath());
        parent::__construct($msg);
    }
}
