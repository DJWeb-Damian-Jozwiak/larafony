<?php

declare(strict_types=1);

namespace Larafony\Framework\Web;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Http\JsonResponse;
use Larafony\Framework\View\Contracts\RendererContract;
use Larafony\Framework\View\ViewManager;
use Psr\Http\Message\ResponseInterface;

abstract class Controller
{
    protected ViewManager $viewManager;

    public function __construct(
        public readonly ContainerContract $container
    ) {
        $this->viewManager = $container->get(ViewManager::class);
    }

    public function withRenderer(RendererContract $renderer): self
    {
        $this->viewManager = $this->viewManager->withRenderer($renderer);
        return $this;
    }

    /**
     * @param string $view
     * @param array<string, mixed> $data
     *
     * @return ResponseInterface
     *
     * @throws \Exception
     */
    public function render(string $view, array $data = []): ResponseInterface
    {
        return $this->viewManager->make($view, $data)->render();
    }

    /**
     * @param array<string, string|array<int, string>> $headers
     */
    public function json(
        mixed $data,
        int $statusCode = 200,
        array $headers = []
    ): ResponseInterface {
        return new JsonResponse($data, $statusCode, $headers);
    }
}
