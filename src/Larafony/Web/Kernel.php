<?php

declare(strict_types=1);

namespace Larafony\Framework\Web;

use Larafony\Framework\Routing\Basic\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Kernel implements RequestHandlerInterface
{
    public function __construct(private Router $router)
    {
    }

    public function handle(ServerRequestInterface $request, ?callable $exitCallback = null): ResponseInterface
    {
        return $this->router->handle($request)
            |> $this->handleHeaders(...)
            |> (fn (ResponseInterface $response) => $this->handleRedirects($response, $exitCallback));
    }

    public function handleHeaders(ResponseInterface $response): ResponseInterface
    {
        http_response_code($response->getStatusCode());
        $headers = array_filter(
            $response->getHeaders(),
            static fn ($name) => $name !== 'Location',
            ARRAY_FILTER_USE_KEY
        );
        foreach ($headers as $name => $values) {
            header($name . ': ' . implode(', ', $values), false);
        }
        return $response;
    }

    public function handleRedirects(ResponseInterface $response, ?callable $callback = null): ResponseInterface
    {
        $callback ??= exit(...);
        if ($response->hasHeader('Location')) {
            $location = $response->getHeaderLine('Location');
            $statusCode = $response->getStatusCode();

            if ($statusCode < 300 || $statusCode >= 400) {
                $statusCode = 302;
            }

            header('Location: ' . $location, true, $statusCode);
            $callback($statusCode);
        }

        return $response;
    }

    public function withRoutes(callable $callback): self
    {
        $callback($this->router);
        return $this;
    }
}
