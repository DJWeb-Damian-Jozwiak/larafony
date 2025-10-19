<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Advanced;

use Larafony\Framework\Core\Helpers\Directory;
use Larafony\Framework\Core\Support\FileContentToClassNameConverter;
use ReflectionClass;

class AttributeRouteScanner
{
    /**
     * @return array<int, ReflectionClass<object>>
     */
    public function scanDirectory(string $path): array
    {
        $files = new Directory($path)->files;
        $files = array_filter($files, static fn (\SplFileInfo $file) => $file->getExtension() === 'php');

        $classes = [];
        foreach ($files as $file) {
            $className = FileContentToClassNameConverter::convert($file->getPathname());

            if ($className && class_exists($className)) {
                $classes[] = new ReflectionClass($className);
            }
        }
        return array_filter($classes, $this->hasRouteAttributes(...));
    }

    /**
     * @param ReflectionClass<object> $class
     */
    public function hasRouteAttributes(ReflectionClass $class): bool
    {
        $attributes = $class->getAttributes();
        $methods = $class->getMethods();
        foreach ($methods as $method) {
            $attributes = [...$attributes, ...$method->getAttributes()];
        }
        return array_any(
            $attributes,
            static fn ($attribute) => str_contains($attribute->getName(), 'Routing\\Advanced\\Attributes\\')
        );
    }
}
