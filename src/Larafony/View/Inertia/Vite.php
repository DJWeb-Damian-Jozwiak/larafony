<?php

declare(strict_types=1);

namespace Larafony\Framework\View\Inertia;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Storage\File;
use Larafony\Framework\Web\Config;

class Vite
{
    private const string VITE_DEV_SERVER = 'http://localhost:5173';
    public function __construct(private readonly ContainerContract $container)
    {
    }

    /**
     * Render Vite asset tags
     *
     * @param array<int, string> $entrypoints
     *
     * @return string
     */
    public function render(array $entrypoints): string
    {
        if ($this->isDevelopment()) {
            return $this->renderDevelopmentTags($entrypoints);
        }

        return $this->renderProductionTags($entrypoints);
    }

    /**
     * Render tags for development (Vite dev server)
     *
     * @param array<int, string> $entrypoints
     *
     * @return string
     */
    private function renderDevelopmentTags(array $entrypoints): string
    {
        $tags = [];

        $entries = array_map(static fn ($entry) => sprintf(
            '<script type="module" src="%s/%s"></script>',
            self::VITE_DEV_SERVER,
            $entry,
        ), $entrypoints);

        $tags = [
            sprintf(
                '<script type="module" src="%s/@vite/client"></script>',
                self::VITE_DEV_SERVER,
            ),
            ...$entries,
        ];

        return implode("\n    ", $tags);
    }

    /**
     * Render tags for production (using manifest)
     *
     * @param array<int, string> $entrypoints
     *
     * @return string
     */
    private function renderProductionTags(array $entrypoints): string
    {
        $manifest = $this->loadManifest();
        $tags = [];

        foreach ($entrypoints as $entry) {
            if (! isset($manifest[$entry])) {
                throw new \RuntimeException(
                    "Entry point '{$entry}' not found in Vite manifest. Did you run 'npm run build'?",
                );
            }
            $asset = $manifest[$entry];

            // Main JS file
            $tags[] = sprintf('<script type="module" src="/build/%s"></script>', $asset['file']);

            $assets = $asset['css'] ?? [];
            $cssMap = array_map(static fn ($css) => sprintf('<link rel="stylesheet" href="/build/%s">', $css), $assets);
            $assets = $asset['imports'] ?? [];
            $importMap = array_map(
                static fn ($css) => sprintf('<link rel="stylesheet" href="/build/%s">', $css),
                $assets,
            );
            $tags = [...$tags, ...$cssMap, ...$importMap];
        }

        return implode("\n    ", $tags);
    }

    /**
     * Load Vite manifest file
     *
     * @return array<string, mixed>
     */
    private function loadManifest(): array
    {
        $path = $this->container->getBinding('base_path');
        $manifestPath = $path . '/public/build/.vite/manifest.json';

        File::ensureFileExists($manifestPath);

        $content = file_get_contents($manifestPath);
        if ($content === false) {
            throw new \RuntimeException("Failed to read Vite manifest at '{$manifestPath}'");
        }

        return json_decode($content, true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * Check if running in development mode
     */
    private function isDevelopment(): bool
    {
        return Config::get('app.env', 'production') === 'local';
    }
}
