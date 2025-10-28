<?php

declare(strict_types=1);

namespace Larafony\Framework\View\Inertia;

use Larafony\Framework\View\ViewManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Inertia
{
    /**
     * @var array<string, mixed>
     */
    public private(set) array $sharedProps = [];

    private string $rootView = 'inertia.blade.php';

    private ?string $version = null;

    public function __construct(
        private readonly ServerRequestInterface $request,
        private readonly ViewManager $viewManager
    ) {
    }

    /**
     * Render an Inertia response
     *
     * @param string $component
     * @param array<string, mixed> $props
     *
     * @return ResponseInterface
     */
    public function render(string $component, array $props = []): ResponseInterface
    {
        $responseFactory = new ResponseFactory($this->request, $this->viewManager);
        $mergedProps = array_merge($this->sharedProps, $props);

        // Resolve lazy props only for partial reloads
        $resolvedProps = $this->resolveProps($mergedProps);

        $page = [
            'component' => $component,
            'props' => $resolvedProps,
            'url' => $this->request->getUri()->getPath(),
            'version' => $this->getVersion(),
        ];

        return $responseFactory->createResponse($page, $this->rootView);
    }

    /**
     * Share data globally with all Inertia responses
     *
     * @param string|array<string, mixed> $key
     * @param mixed $value
     */
    public function share(string|array $key, mixed $value = null): self
    {
        if (is_array($key)) {
            $this->sharedProps = array_merge($this->sharedProps, $key);
        } else {
            $this->sharedProps[$key] = $value;
        }

        return $this;
    }

    public function getRootView(): string
    {
        return $this->rootView;
    }

    public function withRootView(string $view): self
    {
        $this->rootView = $view;
        return $this;
    }

    /**
     * Set asset version for cache invalidation
     *
     * @param string $version Version string (e.g., git hash, build timestamp)
     */
    public function version(string $version): self
    {
        $this->version = $version;
        return $this;
    }

    /**
     * Get the current version
     */
    private function getVersion(): string
    {
        return $this->version ?? '1.0';
    }

    /**
     * Resolve lazy props and handle partial reloads
     *
     * @param array<string, mixed> $props
     *
     * @return array<string, mixed>
     */
    private function resolveProps(array $props): array
    {
        // Check if this is a partial reload
        $only = $this->request->hasHeader('X-Inertia-Partial-Data')
            ? explode(',', $this->request->getHeaderLine('X-Inertia-Partial-Data'))
            : null;

        $partialComponent = $this->request->getHeaderLine('X-Inertia-Partial-Component');

        // If partial reload, filter props
        if ($only !== null && $partialComponent !== '') {
            $props = array_filter(
                $props,
                static fn ($key) => in_array($key, $only, true),
                ARRAY_FILTER_USE_KEY
            );
        }

        // Resolve callable props (lazy evaluation)
        return array_map(
            static fn ($value) => is_callable($value) ? $value() : $value,
            $props
        );
    }
}
