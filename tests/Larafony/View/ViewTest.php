<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\View;

use Larafony\Framework\Http\Response;
use Larafony\Framework\View\Contracts\RendererContract;
use Larafony\Framework\View\Contracts\ViewContract;
use Larafony\Framework\View\View;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ViewTest extends TestCase
{
    private RendererContract $renderer;

    protected function setUp(): void
    {
        $this->renderer = $this->createStub(RendererContract::class);
    }

    public function testViewImplementsViewContract(): void
    {
        $view = new View('test', $this->renderer);

        $this->assertInstanceOf(ViewContract::class, $view);
    }

    public function testViewExtendsPsrResponse(): void
    {
        $view = new View('test', $this->renderer);

        $this->assertInstanceOf(ResponseInterface::class, $view);
        $this->assertInstanceOf(Response::class, $view);
    }

    public function testTemplatePropertyIsAccessible(): void
    {
        $view = new View('home.index', $this->renderer);

        $this->assertEquals('home.index', $view->template);
    }

    public function testTemplatePropertyIsProtectedSet(): void
    {
        $view = new View('home.index', $this->renderer);

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Cannot modify protected(set) property');

        /** @phpstan-ignore-next-line */
        $view->template = 'other';
    }

    public function testDataPropertyIsAccessible(): void
    {
        $view = new View('test', $this->renderer);

        $this->assertEquals([], $view->data);
    }

    public function testDataPropertyIsProtectedSet(): void
    {
        $view = new View('test', $this->renderer);

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Cannot modify protected(set) property');

        /** @phpstan-ignore-next-line */
        $view->data = ['key' => 'value'];
    }

    public function testWithAddsDataToView(): void
    {
        $view = new View('test', $this->renderer);
        $result = $view->with('name', 'John');

        $this->assertSame($view, $result);
        $this->assertEquals(['name' => 'John'], $view->data);
    }

    public function testWithCanBeChained(): void
    {
        $view = new View('test', $this->renderer);
        $result = $view
            ->with('name', 'John')
            ->with('age', 30)
            ->with('active', true);

        $this->assertSame($view, $result);
        $this->assertEquals([
            'name' => 'John',
            'age' => 30,
            'active' => true,
        ], $view->data);
    }

    public function testWithOverridesExistingData(): void
    {
        $view = new View('test', $this->renderer);
        $view->with('name', 'John');
        $view->with('name', 'Jane');

        $this->assertEquals(['name' => 'Jane'], $view->data);
    }

    public function testRenderCallsRendererWithTemplateAndData(): void
    {
        $renderer = $this->createMock(RendererContract::class);
        $renderer
            ->expects($this->once())
            ->method('render')
            ->with('home.index', ['name' => 'John'])
            ->willReturn('<html>Hello John</html>');

        $view = new View('home.index', $renderer);
        $view->with('name', 'John');
        $response = $view->render();

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testRenderReturnsResponseWithRenderedContent(): void
    {
        $renderedContent = '<html><body>Rendered Template</body></html>';
        $this->renderer
            ->method('render')
            ->willReturn($renderedContent);

        $view = new View('test', $this->renderer);
        $response = $view->render();

        $this->assertEquals($renderedContent, (string) $response->getBody());
    }

    public function testRenderWithEmptyData(): void
    {
        $renderer = $this->createMock(RendererContract::class);
        $renderer
            ->expects($this->once())
            ->method('render')
            ->with('empty', [])
            ->willReturn('<html>Empty</html>');

        $view = new View('empty', $renderer);
        $response = $view->render();

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testRenderPreservesDataTypes(): void
    {
        $data = [
            'string' => 'text',
            'int' => 42,
            'bool' => true,
            'null' => null,
            'array' => [1, 2, 3],
        ];

        $renderer = $this->createMock(RendererContract::class);
        $renderer
            ->expects($this->once())
            ->method('render')
            ->with('test', $data);

        $view = new View('test', $renderer);
        foreach ($data as $key => $value) {
            $view->with($key, $value);
        }
        $view->render();
    }

    public function testWithAcceptsVariousDataTypes(): void
    {
        $view = new View('test', $this->renderer);

        $view->with('string', 'text')
            ->with('int', 123)
            ->with('float', 3.14)
            ->with('bool', false)
            ->with('null', null)
            ->with('array', [1, 2])
            ->with('object', new \stdClass());

        $this->assertIsString($view->data['string']);
        $this->assertIsInt($view->data['int']);
        $this->assertIsFloat($view->data['float']);
        $this->assertIsBool($view->data['bool']);
        $this->assertNull($view->data['null']);
        $this->assertIsArray($view->data['array']);
        $this->assertIsObject($view->data['object']);
    }

    public function testRenderReturnsNewResponseInstance(): void
    {
        $this->renderer->method('render')->willReturn('content');

        $view = new View('test', $this->renderer);
        $response1 = $view->render();
        $response2 = $view->render();

        $this->assertNotSame($response1, $response2);
    }
}
