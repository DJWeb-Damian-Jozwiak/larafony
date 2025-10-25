<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\View;

use Larafony\Framework\View\Contracts\RendererContract;
use Larafony\Framework\View\Contracts\ViewContract;
use Larafony\Framework\View\View;
use Larafony\Framework\View\ViewManager;
use PHPUnit\Framework\TestCase;

class ViewManagerTest extends TestCase
{
    private RendererContract $renderer;
    private ViewManager $viewManager;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(RendererContract::class);
        $this->viewManager = new ViewManager($this->renderer);
    }

    public function testMakeCreatesViewInstance(): void
    {
        $view = $this->viewManager->make('test.template');

        $this->assertInstanceOf(ViewContract::class, $view);
        $this->assertInstanceOf(View::class, $view);
    }

    public function testMakePassesTemplateToView(): void
    {
        $view = $this->viewManager->make('home.index');

        $this->assertEquals('home.index', $view->template);
    }

    public function testMakeWithDataPassesDataToView(): void
    {
        $data = ['name' => 'John', 'age' => 30];
        $view = $this->viewManager->make('profile', $data);

        $this->assertEquals($data, $view->data);
    }

    public function testMakeWithEmptyDataCreatesViewWithEmptyArray(): void
    {
        $view = $this->viewManager->make('empty');

        $this->assertEquals([], $view->data);
    }

    public function testRendererPropertyIsAccessible(): void
    {
        $this->assertSame($this->renderer, $this->viewManager->renderer);
    }

    public function testRendererPropertyIsProtectedSet(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Cannot modify protected(set) property');

        /** @phpstan-ignore-next-line */
        $this->viewManager->renderer = $this->createMock(RendererContract::class);
    }

    public function testWithRendererCreatesNewInstance(): void
    {
        $newRenderer = $this->createMock(RendererContract::class);
        $newManager = $this->viewManager->withRenderer($newRenderer);

        $this->assertNotSame($this->viewManager, $newManager);
        $this->assertSame($newRenderer, $newManager->renderer);
        $this->assertSame($this->renderer, $this->viewManager->renderer);
    }

    public function testWithRendererIsImmutable(): void
    {
        $originalRenderer = $this->viewManager->renderer;
        $newRenderer = $this->createMock(RendererContract::class);

        $this->viewManager->withRenderer($newRenderer);

        $this->assertSame($originalRenderer, $this->viewManager->renderer);
    }

    public function testMakeWithMultipleDataKeys(): void
    {
        $data = [
            'title' => 'Welcome',
            'user' => ['name' => 'Alice', 'role' => 'admin'],
            'posts' => [1, 2, 3],
            'active' => true,
        ];

        $view = $this->viewManager->make('dashboard', $data);

        $this->assertEquals($data, $view->data);
    }

    public function testMakePreservesDataTypes(): void
    {
        $data = [
            'string' => 'text',
            'int' => 42,
            'float' => 3.14,
            'bool' => true,
            'null' => null,
            'array' => [1, 2, 3],
        ];

        $view = $this->viewManager->make('types', $data);

        $this->assertSame('text', $view->data['string']);
        $this->assertSame(42, $view->data['int']);
        $this->assertSame(3.14, $view->data['float']);
        $this->assertSame(true, $view->data['bool']);
        $this->assertNull($view->data['null']);
        $this->assertSame([1, 2, 3], $view->data['array']);
    }
}
