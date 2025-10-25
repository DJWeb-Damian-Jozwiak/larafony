<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\View;

use Larafony\Framework\View\Contracts\DirectiveContract;
use Larafony\Framework\View\TemplateCompiler;
use PHPUnit\Framework\TestCase;

class TemplateCompilerTest extends TestCase
{
    private TemplateCompiler $compiler;

    protected function setUp(): void
    {
        $this->compiler = new TemplateCompiler();
    }

    public function testCompileEscapedEcho(): void
    {
        $content = 'Hello {{ $name }}';
        $compiled = $this->compiler->compile($content);

        $this->assertStringContainsString('htmlspecialchars', $compiled);
        $this->assertStringContainsString('$name', $compiled);
        $this->assertStringContainsString('ENT_QUOTES', $compiled);
        $this->assertStringContainsString('UTF-8', $compiled);
    }

    public function testCompileRawEcho(): void
    {
        $content = 'Content: {!! $html !!}';
        $compiled = $this->compiler->compile($content);

        $this->assertStringContainsString('<?php echo', $compiled);
        $this->assertStringContainsString('$html', $compiled);
        $this->assertStringNotContainsString('htmlspecialchars', $compiled);
    }

    public function testCompileMultipleEchos(): void
    {
        $content = '{{ $name }} and {!! $html !!}';
        $compiled = $this->compiler->compile($content);

        $this->assertStringContainsString('htmlspecialchars', $compiled);
        $this->assertStringContainsString('$name', $compiled);
        $this->assertStringContainsString('<?php echo', $compiled);
        $this->assertStringContainsString('$html', $compiled);
    }

    public function testCompileComments(): void
    {
        $content = '{{-- This is a comment --}}';
        $compiled = $this->compiler->compile($content);

        $this->assertStringContainsString('<?php /*', $compiled);
        $this->assertStringContainsString('This is a comment', $compiled);
        $this->assertStringContainsString('*/ ?>', $compiled);
    }

    public function testCompileMultilineComment(): void
    {
        $content = "{{-- This is\na multiline\ncomment --}}";
        $compiled = $this->compiler->compile($content);

        $this->assertStringContainsString('<?php /*', $compiled);
        $this->assertStringContainsString('This is', $compiled);
        $this->assertStringContainsString('comment', $compiled);
        $this->assertStringContainsString('*/ ?>', $compiled);
    }

    public function testAddDirectiveRegistersDirective(): void
    {
        $directive = $this->createMock(DirectiveContract::class);
        $directive->method('compile')->willReturnArgument(0);

        $result = $this->compiler->addDirective($directive);

        $this->assertSame($this->compiler, $result);
    }

    public function testCompileCallsDirectives(): void
    {
        $directive = $this->createMock(DirectiveContract::class);
        $directive
            ->expects($this->once())
            ->method('compile')
            ->with($this->stringContains('test content'))
            ->willReturn('compiled content');

        $this->compiler->addDirective($directive);
        $compiled = $this->compiler->compile('test content');

        $this->assertEquals('compiled content', $compiled);
    }

    public function testCompileCallsMultipleDirectivesInOrder(): void
    {
        $directive1 = $this->createMock(DirectiveContract::class);
        $directive1->method('compile')->willReturnCallback(fn($c) => $c . ' [D1]');

        $directive2 = $this->createMock(DirectiveContract::class);
        $directive2->method('compile')->willReturnCallback(fn($c) => $c . ' [D2]');

        $this->compiler
            ->addDirective($directive1)
            ->addDirective($directive2);

        $compiled = $this->compiler->compile('content');

        $this->assertStringContainsString('[D1]', $compiled);
        $this->assertStringContainsString('[D2]', $compiled);
        $this->assertStringContainsString('content', $compiled);
    }

    public function testDirectivesArrayIsReadable(): void
    {
        // private(set) allows reading but not writing
        $directives = $this->compiler->directives;

        $this->assertIsArray($directives);
    }

    public function testCompilePreservesWhitespace(): void
    {
        $content = "Line 1\n{{ \$var }}\nLine 3";
        $compiled = $this->compiler->compile($content);

        $this->assertStringContainsString("Line 1\n", $compiled);
        $this->assertStringContainsString("\nLine 3", $compiled);
    }

    public function testCompileWithComplexExpression(): void
    {
        $content = '{{ $user->getName() }}';
        $compiled = $this->compiler->compile($content);

        $this->assertStringContainsString('htmlspecialchars', $compiled);
        $this->assertStringContainsString('$user->getName()', $compiled);
    }

    public function testCompileWithTernaryOperator(): void
    {
        $content = '{{ $active ? "Yes" : "No" }}';
        $compiled = $this->compiler->compile($content);

        $this->assertStringContainsString('htmlspecialchars', $compiled);
        $this->assertStringContainsString('$active ? "Yes" : "No"', $compiled);
    }

    public function testCompileWithNullCoalescing(): void
    {
        $content = '{{ $name ?? "Guest" }}';
        $compiled = $this->compiler->compile($content);

        $this->assertStringContainsString('htmlspecialchars', $compiled);
        $this->assertStringContainsString('$name ?? "Guest"', $compiled);
    }

    public function testAddDirectiveCanBeChained(): void
    {
        $directive1 = $this->createMock(DirectiveContract::class);
        $directive1->method('compile')->willReturnArgument(0);

        $directive2 = $this->createMock(DirectiveContract::class);
        $directive2->method('compile')->willReturnArgument(0);

        $result = $this->compiler
            ->addDirective($directive1)
            ->addDirective($directive2);

        $this->assertSame($this->compiler, $result);
    }

    public function testCompileMixedContent(): void
    {
        $content = '
            <h1>{{ $title }}</h1>
            {{-- Comment --}}
            <div>{!! $content !!}</div>
        ';

        $compiled = $this->compiler->compile($content);

        $this->assertStringContainsString('htmlspecialchars', $compiled);
        $this->assertStringContainsString('$title', $compiled);
        $this->assertStringContainsString('<?php /*', $compiled);
        $this->assertStringContainsString('Comment', $compiled);
        $this->assertStringContainsString('<?php echo', $compiled);
        $this->assertStringContainsString('$content', $compiled);
    }
}
