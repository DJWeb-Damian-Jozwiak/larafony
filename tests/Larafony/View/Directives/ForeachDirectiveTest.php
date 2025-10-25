<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\View\Directives;

use Larafony\Framework\View\Contracts\DirectiveContract;
use Larafony\Framework\View\Directives\ForeachDirective;
use PHPUnit\Framework\TestCase;

class ForeachDirectiveTest extends TestCase
{
    private ForeachDirective $directive;

    protected function setUp(): void
    {
        $this->directive = new ForeachDirective();
    }

    public function testImplementsDirectiveContract(): void
    {
        $this->assertInstanceOf(DirectiveContract::class, $this->directive);
    }

    public function testCompileSimpleForeach(): void
    {
        $content = '@foreach($items as $item) {{ $item }} @endforeach';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('<?php foreach($items as $item): ?>', $compiled);
        $this->assertStringContainsString('<?php endforeach; ?>', $compiled);
    }

    public function testCompileForeachWithKey(): void
    {
        $content = '@foreach($users as $id => $user) User @endforeach';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('<?php foreach($users as $id => $user): ?>', $compiled);
    }

    public function testCompileForeachWithArrayAccess(): void
    {
        $content = '@foreach($data["items"] as $item) Item @endforeach';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('<?php foreach($data["items"] as $item): ?>', $compiled);
    }

    public function testCompileForeachWithMethodCall(): void
    {
        $content = '@foreach($user->getPosts() as $post) Post @endforeach';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('<?php foreach(', $compiled);
        $this->assertStringContainsString('$user->getPosts()', $compiled);
        $this->assertStringContainsString('as $post', $compiled);
    }

    public function testCompileNestedForeach(): void
    {
        $content = '@foreach($categories as $cat) @foreach($cat->items as $item) Item @endforeach @endforeach';
        $compiled = $this->directive->compile($content);

        $this->assertEquals(2, substr_count($compiled, '<?php foreach'));
        $this->assertEquals(2, substr_count($compiled, '<?php endforeach;'));
    }

    public function testCompileMultipleForeachBlocks(): void
    {
        $content = '@foreach($a as $x) X @endforeach @foreach($b as $y) Y @endforeach';
        $compiled = $this->directive->compile($content);

        $this->assertEquals(2, substr_count($compiled, '<?php foreach'));
        $this->assertEquals(2, substr_count($compiled, '<?php endforeach;'));
    }

    public function testCompileForeachWithComplexKey(): void
    {
        $content = '@foreach($items as $key => $value) {{ $key }}: {{ $value }} @endforeach';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('<?php foreach($items as $key => $value): ?>', $compiled);
    }

    public function testCompileForeachWithWhitespace(): void
    {
        $content = '@foreach  (  $items as $item  )  Content  @endforeach';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('<?php foreach(  $items as $item  ): ?>', $compiled);
    }

    public function testCompileForeachWithoutSpaces(): void
    {
        $content = '@foreach($x as $y)text@endforeach';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('<?php foreach($x as $y): ?>', $compiled);
        $this->assertStringContainsString('text', $compiled);
    }

    public function testCompilePreservesContentBetweenDirectives(): void
    {
        $content = 'Before @foreach($items as $item) Middle @endforeach After';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('Before', $compiled);
        $this->assertStringContainsString('Middle', $compiled);
        $this->assertStringContainsString('After', $compiled);
    }

    public function testCompileForeachWithReference(): void
    {
        $content = '@foreach($items as &$item) Modify @endforeach';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('<?php foreach($items as &$item): ?>', $compiled);
    }

    public function testCompileForeachWithObjectProperty(): void
    {
        $content = '@foreach($object->property as $item) Item @endforeach';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('<?php foreach($object->property as $item): ?>', $compiled);
    }

    public function testCompileForeachWithStaticProperty(): void
    {
        $content = '@foreach(Class::$items as $item) Item @endforeach';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('<?php foreach(Class::$items as $item): ?>', $compiled);
    }

    public function testCompileForeachWithArrayRange(): void
    {
        $content = '@foreach(range(1, 10) as $num) Number @endforeach';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('<?php foreach(', $compiled);
        $this->assertStringContainsString('range(1, 10)', $compiled);
        $this->assertStringContainsString('as $num', $compiled);
    }

    public function testCompileForeachInsideIf(): void
    {
        $content = 'Content @foreach($items as $item) Item @endforeach More';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('<?php foreach($items as $item): ?>', $compiled);
        $this->assertStringContainsString('<?php endforeach; ?>', $compiled);
        $this->assertStringContainsString('Content', $compiled);
        $this->assertStringContainsString('More', $compiled);
    }
}
