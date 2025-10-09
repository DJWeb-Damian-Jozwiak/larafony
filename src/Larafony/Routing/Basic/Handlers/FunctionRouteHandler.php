<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Basic\Handlers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class FunctionRouteHandler implements RequestHandlerInterface
{
    private string $function {
        get => $this->function;
        set {
            if (! function_exists($value)) {
                throw new \InvalidArgumentException(sprintf('Function %s does not exist', $value));
            }
            $this->function = $value;
        }
    }

    public function __construct(string $function)
    {
        $this->function = $function;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $function = $this->function;
        // @phpstan-ignore callable.nonCallable
        return $function($request);
    }
}
