<?php

declare(strict_types=1);

namespace Larafony\Framework\View;

use Larafony\Framework\View\Contracts\RendererContract;
use Larafony\Framework\View\Engines\BladeAdapter;
use ReflectionClass;
use ReflectionProperty;

abstract class Component
{
    private static ?RendererContract $sharedRenderer = null;

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
        // Use shared renderer if available, otherwise create new one
        $renderer = self::$sharedRenderer ?? BladeAdapter::buildDefault();

        // If this is the first component (no shared renderer yet), set it
        if (self::$sharedRenderer === null) {
            self::$sharedRenderer = $renderer;
        }

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
        $properties = array_filter($properties, static fn (ReflectionProperty $property) => ! $property->isStatic());

        $publicProperties = [];
        foreach ($properties as $property) {
            $publicProperties[$property->getName()] = $property->getValue($this);
        }

        return $publicProperties;
    }
}
