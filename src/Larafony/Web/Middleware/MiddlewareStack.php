<?php

declare(strict_types=1);

namespace Larafony\Framework\Web\Middleware;

use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Routing\Advanced\Router;
use Larafony\Framework\Routing\Middleware\RouterMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewareStack implements RequestHandlerInterface
{
    /**
     * @var array<int, MiddlewareInterface>
     */
    private array $middleware = [];
    private int $currentIndex = 0;
    private bool $routerExecuted = false;
    private ResponseInterface $originalResponse;

    public function __construct(private readonly Router $router)
    {
        $this->originalResponse = new ResponseFactory()->createResponse();
    }

    public function add(MiddlewareInterface $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    public function remove(MiddlewareInterface $middleware): self
    {
        $this->middleware = array_filter(
            $this->middleware,
            static fn (MiddlewareInterface $item) => $item::class !== $middleware::class
        );
        return $this;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $exception = $request->getAttribute('exception');
        if($exception !== null) {
            throw $exception;
        }

        if ($this->currentIndex === count($this->middleware) && $this->routerExecuted) {
            return $request->getAttribute('route_response', $this->originalResponse);
        }
        if ($this->currentIndex === count($this->middleware))
        {
            $this->routerExecuted = true;
            $this->originalResponse = $this->router->handle($request);
        }
        $middleware = $this->middleware[$this->currentIndex];
        if ($middleware instanceof RouterMiddleware)
        {
            $this->routerExecuted = true;
        }

        $this->currentIndex++;
        //continue process middleware
        return $middleware->process($request, $this);
    }
}