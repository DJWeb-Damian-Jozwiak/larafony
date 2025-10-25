<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\View;

use Larafony\Framework\View\Component;
use PHPUnit\Framework\TestCase;

class ComponentTest extends TestCase
{
    private function createTestComponent(
        string $view = 'test.component',
        array $publicProperties = []
    ): Component {
        return new class($view, $publicProperties) extends Component {
            private string $viewName;
            private array $props;

            public function __construct(string $viewName, array $props)
            {
                $this->viewName = $viewName;
                $this->props = $props;

                foreach ($props as $key => $value) {
                    $this->{$key} = $value;
                }
            }

            protected function getView(): string
            {
                return $this->viewName;
            }
        };
    }

    public function testComponentCanBeInstantiated(): void
    {
        $component = $this->createTestComponent();

        $this->assertInstanceOf(Component::class, $component);
    }

    public function testWithSlotSetsSlotContent(): void
    {
        $component = new class extends Component {
            protected function getView(): string
            {
                return 'test';
            }

            public function getSlotForTest(): ?string
            {
                $reflection = new \ReflectionProperty(Component::class, 'slot');
                return $reflection->getValue($this);
            }
        };

        $component->withSlot('<p>Slot content</p>');

        $this->assertEquals('<p>Slot content</p>', $component->getSlotForTest());
    }

    public function testWithNamedSlotAddsNamedSlot(): void
    {
        $component = new class extends Component {
            protected function getView(): string
            {
                return 'test';
            }

            public function getSlotsForTest(): array
            {
                $reflection = new \ReflectionProperty(Component::class, 'slots');
                return $reflection->getValue($this);
            }
        };

        $component->withNamedSlot('header', '<h1>Header</h1>');
        $slots = $component->getSlotsForTest();

        $this->assertArrayHasKey('header', $slots);
        $this->assertEquals('<h1>Header</h1>', $slots['header']);
    }

    public function testWithNamedSlotSupportsMultipleSlots(): void
    {
        $component = new class extends Component {
            protected function getView(): string
            {
                return 'test';
            }

            public function getSlotsForTest(): array
            {
                $reflection = new \ReflectionProperty(Component::class, 'slots');
                return $reflection->getValue($this);
            }
        };

        $component->withNamedSlot('header', '<h1>Header</h1>');
        $component->withNamedSlot('footer', '<footer>Footer</footer>');
        $component->withNamedSlot('sidebar', '<aside>Sidebar</aside>');

        $slots = $component->getSlotsForTest();

        $this->assertCount(3, $slots);
        $this->assertEquals('<h1>Header</h1>', $slots['header']);
        $this->assertEquals('<footer>Footer</footer>', $slots['footer']);
        $this->assertEquals('<aside>Sidebar</aside>', $slots['sidebar']);
    }

    public function testGetPublicPropertiesExtractsPublicProperties(): void
    {
        $component = new class('Test Title', true, 42) extends Component {
            public string $title;
            public bool $active;
            public int $count;

            public function __construct(string $title, bool $active, int $count)
            {
                $this->title = $title;
                $this->active = $active;
                $this->count = $count;
            }

            protected function getView(): string
            {
                return 'test';
            }

            public function getPropsForTest(): array
            {
                $reflection = new \ReflectionClass($this);
                $method = $reflection->getMethod('getPublicProperties');
                return $method->invoke($this);
            }
        };

        $properties = $component->getPropsForTest();

        $this->assertArrayHasKey('title', $properties);
        $this->assertEquals('Test Title', $properties['title']);
        $this->assertArrayHasKey('active', $properties);
        $this->assertTrue($properties['active']);
        $this->assertArrayHasKey('count', $properties);
        $this->assertEquals(42, $properties['count']);
    }

    public function testGetPublicPropertiesExcludesPrivateProperties(): void
    {
        $component = new class extends Component {
            public string $publicProp = 'public';
            private string $privateProp = 'private';

            protected function getView(): string
            {
                return 'test';
            }

            public function getPropsForTest(): array
            {
                $reflection = new \ReflectionClass($this);
                $method = $reflection->getMethod('getPublicProperties');
                return $method->invoke($this);
            }
        };

        $properties = $component->getPropsForTest();

        $this->assertArrayHasKey('publicProp', $properties);
        $this->assertArrayNotHasKey('privateProp', $properties);
    }

    public function testComponentSupportsVariousDataTypes(): void
    {
        $component = new class('text', 123, 3.14, true, [1, 2, 3], null) extends Component {
            public string $string;
            public int $int;
            public float $float;
            public bool $bool;
            public array $array;
            public mixed $null;

            public function __construct(string $string, int $int, float $float, bool $bool, array $array, mixed $null)
            {
                $this->string = $string;
                $this->int = $int;
                $this->float = $float;
                $this->bool = $bool;
                $this->array = $array;
                $this->null = $null;
            }

            protected function getView(): string
            {
                return 'test';
            }

            public function getPropsForTest(): array
            {
                $reflection = new \ReflectionClass($this);
                $method = $reflection->getMethod('getPublicProperties');
                return $method->invoke($this);
            }
        };

        $properties = $component->getPropsForTest();

        $this->assertIsString($properties['string']);
        $this->assertIsInt($properties['int']);
        $this->assertIsFloat($properties['float']);
        $this->assertIsBool($properties['bool']);
        $this->assertIsArray($properties['array']);
        $this->assertNull($properties['null']);
    }

    public function testGetViewReturnsTemplateName(): void
    {
        $component = new class extends Component {
            protected function getView(): string
            {
                return 'my.custom.template';
            }
        };

        $reflection = new \ReflectionClass($component);
        $method = $reflection->getMethod('getView');

        $this->assertEquals('my.custom.template', $method->invoke($component));
    }

}
