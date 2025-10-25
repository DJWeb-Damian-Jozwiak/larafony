<?php

declare(strict_types=1);

namespace Larafony\Framework\View;

use Larafony\Framework\View\Engines\BladeAdapter;
use ReflectionClass;
use ReflectionProperty;

abstract class Component
{
    private ?string $slot = null;
    /**
     * @var array<string, string>
     */
    private array $slots = [];

    public function withSlot(string $content): void
    {
        $this->slot = $content;
    }

    public function withNamedSlot(string $name, string $content): void
    {
        $this->slots[$name] = $content;
    }

    public function render(): string
    {
        $renderer = BladeAdapter::buildDefault();

        return $renderer->render(
            $this->getView(),
            array_merge(
                $this->getPublicProperties(),
                [
                    'slot' => $this->slot,
                    'slots' => $this->slots,
                ]
            )
        );
    }

    abstract protected function getView(): string;

    /**
     * @return array<string, mixed>
     */
    private function getPublicProperties(): array
    {
        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        $publicProperties = [];
        foreach ($properties as $property) {
            if (! $property->isStatic()) {
                $publicProperties[$property->getName()] = $property->getValue($this);
            }
        }

        return $publicProperties;
    }
}
