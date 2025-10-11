<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Formatters;

use Larafony\Framework\Console\Formatters\Styles\NormalStyle;
use Larafony\Framework\Container\Contracts\ContainerContract;

final class OutputFormatter
{
    /**
     * @var array<string, OutputFormatterStyle>
     */
    private array $styles = [];

    public function __construct(private readonly ContainerContract $container)
    {
    }

    public function withStyle(string $name, OutputFormatterStyle $style): void
    {
        $this->styles[$name] = $style;
        $this->container->set(self::class, $this);
    }

    public function getStyle(string $name): OutputFormatterStyle
    {
        return $this->styles[$name] ?? new NormalStyle();
    }

    public function format(string $message): string
    {
        return preg_replace_callback(
            '/<([a-z-]+)>(.*?)<\/[a-z-]+>/i',
            function ($matches) {
                $style = $this->getStyle($matches[1]);
                return $style->apply($matches[2]);
            },
            $message
        ) ?? '';
    }
}
