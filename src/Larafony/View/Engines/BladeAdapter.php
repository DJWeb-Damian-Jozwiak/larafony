<?php

declare(strict_types=1);

namespace Larafony\Framework\View\Engines;

use Larafony\Framework\Storage\File;
use Larafony\Framework\View\AssetManager;
use Larafony\Framework\View\Contracts\RendererContract;
use Larafony\Framework\View\Directives\ComponentDirective;
use Larafony\Framework\View\Directives\DoWhileDirective;
use Larafony\Framework\View\Directives\EmptyDirective;
use Larafony\Framework\View\Directives\ExtendDirective;
use Larafony\Framework\View\Directives\ForDirective;
use Larafony\Framework\View\Directives\ForeachDirective;
use Larafony\Framework\View\Directives\IfDirective;
use Larafony\Framework\View\Directives\IssetDirective;
use Larafony\Framework\View\Directives\SectionDirective;
use Larafony\Framework\View\Directives\SlotDirective;
use Larafony\Framework\View\Directives\StackDirective;
use Larafony\Framework\View\Directives\SwitchDirective;
use Larafony\Framework\View\Directives\UnlessDirective;
use Larafony\Framework\View\Directives\ViteDirective;
use Larafony\Framework\View\Directives\WhileDirective;
use Larafony\Framework\View\Directives\YieldDirective;
use Larafony\Framework\View\TemplateCompiler;
use Larafony\Framework\View\TemplateLoader;
use Larafony\Framework\Web\Config;

class BladeAdapter extends BaseAdapter implements RendererContract
{
    private static int $renderDepth = 0;

    private TemplateCompiler $compiler;
    private TemplateLoader $loader;
    private AssetManager $assetManager;

    private ?string $extendedTemplate = null;
    /**
     * @var array<string, string>
     */
    private array $sections = [];
    private string $currentSection = '';

    public function __construct(
        private readonly string $template_path,
        private readonly string $cache_path,
        public readonly string $componentNamespace = '\\App\\View\\Components',
    ) {
        $this->compiler = new TemplateCompiler();
        $this->loader = new TemplateLoader($template_path);
        $this->assetManager = new AssetManager();
        $this->registerDefaultDirectives();
    }

    public function extend(string $template): void
    {
        $this->extendedTemplate = $template;
    }

    public function section(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }

    public function endSection(): void
    {
        if ($this->currentSection) {
            /** @var string $buffer */
            $buffer = ob_get_clean();
            $this->sections[$this->currentSection] = $buffer;
            $this->currentSection = '';
        }
    }

    public function yield(string $section): string
    {
        return $this->sections[$section] ?? '';
    }

    public function pushToStack(string $stack, string $content): void
    {
        $this->assetManager->push($stack, $content);
    }

    public function renderStack(string $stack): string
    {
        return $this->assetManager->render($stack);
    }

    public function render(string $template, array $data = []): string
    {
        self::$renderDepth++;

        try {
            $this->extendedTemplate = null;

            $content = $this->renderTemplate($template, $data);

            /**
             * template is extended in views, in a render template method with layouts
             */
            /** @phpstan-ignore-next-line */
            if ($this->extendedTemplate !== null) {
                return $this->render($this->extendedTemplate, $data);
            }

            return $content;
        } finally {
            self::$renderDepth--;
        }
    }

    public static function buildDefault(): RendererContract
    {
        $config = Config::get('views.engines.blade');
        $template_path = $config['paths']['template_path'];
        $cache_path = $config['paths']['cache_path'];
        $namespace = $config['components']['namespace'];
        return new BladeAdapter($template_path, $cache_path, $namespace);
    }

    /**
     * @param string $template
     * @param array<string, mixed> $data
     *
     * @return string
     */
    private function renderTemplate(string $template, array $data): string
    {
        $cached_file = $this->getCachedPath($template);
        if (! $this->isCached($template, $cached_file)) {
            $content = $this->loader->load($template);
            $compiled = $this->compiler->compile($content);
            $this->cache($cached_file, $compiled);
        }

        return $this->evaluateTemplate($cached_file, $data);
    }

    private function registerDefaultDirectives(): void
    {
        // Create ComponentDirective and give it access to the compiler
        $componentDirective = new ComponentDirective();
        $componentDirective->setCompiler($this->compiler);

        $this->compiler
            // Control flow directives must be compiled BEFORE components
            ->addDirective(new IfDirective())
            ->addDirective(new UnlessDirective())
            //foreach before for!
            ->addDirective(new ForeachDirective())
            ->addDirective(new ForDirective())
            ->addDirective(new WhileDirective())
            ->addDirective(new DoWhileDirective())
            ->addDirective(new SwitchDirective())
            ->addDirective(new IssetDirective())
            ->addDirective(new EmptyDirective())
            // Layout directives
            ->addDirective(new ExtendDirective())
            ->addDirective(new SectionDirective())
            ->addDirective(new YieldDirective())
            // Component directives (must be after control flow)
            ->addDirective($componentDirective)
            ->addDirective(new SlotDirective())
            ->addDirective(new StackDirective())
            // Inertia/Asset directives
            ->addDirective(new ViteDirective());
    }

    private function isCached(string $template, string $cached_file): bool
    {
        // Convert dot notation to directory separator
        $template = str_replace('.', '/', $template) . '.blade.php';

        $template_path = $this->template_path . '/' . $template;
        return File::isCached($template_path, $cached_file);
    }

    private function getCachedPath(string $template): string
    {
        return $this->cache_path . '/' . md5($template) . '.php';
    }

    private function cache(string $path, string $content): void
    {
        File::create($path, $content);
    }

    /**
     * @param string $cached_file
     * @param array<string, mixed> $data
     *
     * @return string
     */
    private function evaluateTemplate(string $cached_file, array $data): string
    {
        extract($data);
        ob_start();
        // Use include (not include_once) to allow multiple instances of the same component
        // template to be rendered within a single request (e.g., multiple <x-status-badge> components)
        include $cached_file;
        /** @var string $buffer */
        $buffer = ob_get_clean();
        return $buffer;
    }
}
