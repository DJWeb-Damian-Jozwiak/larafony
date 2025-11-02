<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Contracts;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Hook that runs before parameter resolution
 * Allows handlers to modify request (e.g., add validated FormRequest to attributes)
 */
interface PreResolutionHookContract
{
    /**
     * Modify request before parameter resolution
     *
     * @param ServerRequestInterface $request Original request
     * @param callable $callable The callable that will be invoked
     *
     * @return ServerRequestInterface Modified request with additional attributes
     */
    public function beforeResolution(ServerRequestInterface $request, callable $callable): ServerRequestInterface;
}
