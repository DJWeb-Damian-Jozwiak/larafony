<?php

declare(strict_types=1);

namespace Larafony\Framework\View\Directives;

use Larafony\Framework\Console\Input\ValueCaster;
use Larafony\Framework\View\Engines\BladeAdapter;
use Larafony\Framework\View\TemplateCompiler;

class ComponentDirective extends Directive
{
    private ?TemplateCompiler $compiler = null;

    public function setCompiler(TemplateCompiler $compiler): void
    {
        $this->compiler = $compiler;
    }

    public function compile(string $content): string
    {
        // named slots (@slot('name'))
        $content = $this->compilePattern(
            '/\@slot\([\'"](.*?)[\'"]\)(.*?)\@endslot/s',
            $content,
            function ($matches) {
                $slotName = $matches[1];
                $slotContent = $this->compileComponents($matches[2]);
                return "<?php \$__slot_component = \$__component; ob_start(); ?>
{$slotContent}<?php \$__slot_component->withNamedSlot('{$slotName}', trim(ob_get_clean())); ?>";
            }
        );

        // render components and nested children
        return $this->compileComponents($content);
    }

    public function getPhpCompiledString(
        string $varName,
        string $componentName,
        string $attributes,
        string $compiledSlot
    ): string {
        /** @var BladeAdapter $adapter */
        $adapter = BladeAdapter::buildDefault();
        $namespace = rtrim($adapter->componentNamespace, '\\');
        return "<?php
                    \$__prev_component = \$__component ?? null;
                    {$varName} = new {$namespace}\\{$componentName}({$attributes});
                    \$__component = {$varName};
                    ob_start();
                    ?>{$compiledSlot}<?php
                    \$__component->withSlot(trim(ob_get_clean()));
                    echo \$__component->render();
                    \$__component = \$__prev_component;
                ?>";
    }

    private function compileComponents(string $content): string
    {
        static $globalCounter = 0;

        return preg_replace_callback(
            '/\<x-([^>]+)(?:\s([^>]*))?\>(.*?)\<\/x-\1\>/s',
            function ($matches) use (&$globalCounter) {
                $globalCounter++;
                $uniqueId = $globalCounter . '_' . substr(md5($matches[0]), 0, 8);

                $componentName = $this->formatComponentName($matches[1]);
                $attributes = $this->parseAttributes($matches[2]);
                $slot = $matches[3];

                $varName = "\$__component_{$uniqueId}";

                // First recursively compile nested components
                $compiledSlot = $this->compileComponents($slot);

                // Then compile all other Blade directives (if, foreach, etc.) using the full compiler
                if ($this->compiler !== null) {
                    $compiledSlot = $this->compiler->compile($compiledSlot);
                }

                return $this->getPhpCompiledString($varName, $componentName, $attributes, $compiledSlot);
            },
            $content
        ) ?? '';
    }

    private function parseAttributes(string $attributesString): string
    {
        // Match both regular attributes and bound attributes (:attribute)
        preg_match_all('/:?(\w+)=[\'"](.*?)[\'"]/', $attributesString, $matches, PREG_SET_ORDER);

        $attributes = [];
        array_walk($matches, function (&$match) use (&$attributes): void {
            $fullMatch = $match[0];
            $key = $match[1];
            $value = $match[2];

            // Check if this is a bound attribute (starts with :)
            $isBound = str_starts_with($fullMatch, ':');

            if (!$isBound) {
                $value = $this->castBool($value);
            }

            $attributes[$key] = [
                'value' => $value,
                'bound' => $isBound
            ];
        });

        return implode(', ', array_map(
            static function ($key, $attrData) {
                $value = $attrData['value'];
                $isBound = $attrData['bound'];

                if (is_bool($value)) {
                    return "{$key}: " . ($value ? 'true' : 'false');
                }

                // If bound (e.g., :title="$title"), output the variable without quotes
                if ($isBound) {
                    return "{$key}: {$value}";
                }

                // Regular attribute, wrap in quotes
                return "{$key}: '{$value}'";
            },
            array_keys($attributes),
            $attributes
        ));
    }

    private function castBool(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }
        $booleans = ['true', 'false'];
        return in_array($value, $booleans, true) ? ValueCaster::cast($value) : $value;
    }

    private function formatComponentName(string $name): string
    {
        $name = str_replace('-', ' ', $name);
        $name = ucwords($name);
        return str_replace(' ', '', $name);
    }
}
