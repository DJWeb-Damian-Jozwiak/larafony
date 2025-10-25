<?php

declare(strict_types=1);

namespace Larafony\Framework\View\Directives;

use Larafony\Framework\Console\Input\ValueCaster;
use Larafony\Framework\View\Engines\BladeAdapter;

class ComponentDirective extends Directive
{
    public function compile(string $content): string
    {
        // named slots (@slot('name'))
        $content = $this->compilePattern(
            '/\@slot\([\'"](.*?)[\'"]\)(.*?)\@endslot/s',
            $content,
            function ($matches) {
                $slotName = $matches[1];
                $slotContent = $this->compileComponents($matches[2]);
                return "<?php \$__current_component = \$__component; ob_start(); ?>
{$slotContent}<?php \$__component->withNamedSlot('{$slotName}', trim(ob_get_clean())); ?>";
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
        $namespace = $adapter->componentNamespace;
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
        return preg_replace_callback(
            '/\<x-([^>]+)(?:\s([^>]*))?\>(.*?)\<\/x-\1\>/s',
            function ($matches) {
                static $counter = 0;
                $counter++;

                $componentName = $this->formatComponentName($matches[1]);
                $attributes = $this->parseAttributes($matches[2]);
                $slot = $matches[3];

                $varName = "\$__component_{$counter}";

                $compiledSlot = $this->compileComponents($slot);

                return $this->getPhpCompiledString($varName, $componentName, $attributes, $compiledSlot);
            },
            $content
        ) ?? '';
    }

    private function parseAttributes(string $attributesString): string
    {
        preg_match_all('/(\w+)=[\'"](.*?)[\'"]/', $attributesString, $matches, PREG_SET_ORDER);

        $attributes = [];
        array_walk($matches, function (&$match) use (&$attributes): void {
            $value = $this->castBool($match[2]);
            $attributes[$match[1]] = $value;
        });

        return implode(', ', array_map(
            static function ($key, $value) {
                if (is_bool($value)) {
                    return "{$key}: " . ($value ? 'true' : 'false');
                }
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
