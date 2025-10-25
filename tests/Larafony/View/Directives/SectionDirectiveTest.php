<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\View\Directives;

use Larafony\Framework\View\Contracts\DirectiveContract;
use Larafony\Framework\View\Directives\SectionDirective;
use PHPUnit\Framework\TestCase;

class SectionDirectiveTest extends TestCase
{
    private SectionDirective $directive;

    protected function setUp(): void
    {
        $this->directive = new SectionDirective();
    }

    public function testImplementsDirectiveContract(): void
    {
        $this->assertInstanceOf(DirectiveContract::class, $this->directive);
    }

    public function testCompileSimpleSection(): void
    {
        $content = '@section("content") Content here @endsection';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('$this->section(', $compiled);
        $this->assertStringContainsString('content', $compiled);
        $this->assertStringContainsString('Content here', $compiled);
        $this->assertStringContainsString('$this->endSection()', $compiled);
    }

    public function testCompileSectionWithSingleQuotes(): void
    {
        $content = "@section('sidebar') Sidebar @endsection";
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('$this->section(', $compiled);
        $this->assertStringContainsString("'sidebar'", $compiled);
    }

    public function testCompileMultipleSections(): void
    {
        $content = '@section("header") Header @endsection @section("footer") Footer @endsection';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('header', $compiled);
        $this->assertStringContainsString('footer', $compiled);
        $this->assertEquals(2, substr_count($compiled, '$this->endSection()'));
    }

    public function testCompileSectionWithNestedContent(): void
    {
        $content = '@section("main") <div class="content"> Content </div> @endsection';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('$this->section(', $compiled);
        $this->assertStringContainsString('main', $compiled);
        $this->assertStringContainsString('<div class="content">', $compiled);
    }

    public function testCompileSectionWithVariableName(): void
    {
        // SectionDirective only supports string literals, not variables
        // This test should verify that variables are not compiled
        $content = '@section($sectionName) Dynamic section @endsection';
        $compiled = $this->directive->compile($content);

        // Variable won't be compiled - it will stay as is
        $this->assertStringContainsString('@section($sectionName)', $compiled);
        $this->assertStringContainsString('Dynamic section', $compiled);
    }

    public function testCompilePreservesContentBetweenSections(): void
    {
        $content = 'Before @section("test") Middle @endsection After';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('Before', $compiled);
        $this->assertStringContainsString('Middle', $compiled);
        $this->assertStringContainsString('After', $compiled);
    }

    public function testCompileSectionWithMultilineContent(): void
    {
        $content = "@section('content')\n  Line 1\n  Line 2\n@endsection";
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString("Line 1", $compiled);
        $this->assertStringContainsString("Line 2", $compiled);
    }

}
