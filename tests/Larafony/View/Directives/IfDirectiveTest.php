<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\View\Directives;

use Larafony\Framework\View\Contracts\DirectiveContract;
use Larafony\Framework\View\Directives\IfDirective;
use PHPUnit\Framework\TestCase;

class IfDirectiveTest extends TestCase
{
    private IfDirective $directive;

    protected function setUp(): void
    {
        $this->directive = new IfDirective();
    }

    public function testImplementsDirectiveContract(): void
    {
        $this->assertInstanceOf(DirectiveContract::class, $this->directive);
    }

    public function testCompileSimpleIf(): void
    {
        $content = '@if($active) Active @endif';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('<?php if($active): ?>', $compiled);
        $this->assertStringContainsString('<?php endif; ?>', $compiled);
    }

    public function testCompileIfWithElse(): void
    {
        $content = '@if($user) Logged in @else Guest @endif';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('<?php if($user): ?>', $compiled);
        $this->assertStringContainsString('<?php else: ?>', $compiled);
        $this->assertStringContainsString('<?php endif; ?>', $compiled);
    }

    public function testCompileIfWithElseIf(): void
    {
        $content = '@if($role === "admin") Admin @elseif($role === "user") User @else Guest @endif';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('<?php if($role === "admin"): ?>', $compiled);
        $this->assertStringContainsString('<?php elseif($role === "user"): ?>', $compiled);
        $this->assertStringContainsString('<?php else: ?>', $compiled);
        $this->assertStringContainsString('<?php endif; ?>', $compiled);
    }

    public function testCompileIfWithComplexCondition(): void
    {
        $content = '@if($user && $user->isActive()) Show @endif';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('<?php if(', $compiled);
        $this->assertStringContainsString('$user && $user->isActive()', $compiled);
        $this->assertStringContainsString('Show', $compiled);
        $this->assertStringContainsString('<?php endif;', $compiled);
    }

    public function testCompileMultipleElseIf(): void
    {
        $content = '@if($x === 1) One @elseif($x === 2) Two @elseif($x === 3) Three @endif';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('<?php if($x === 1): ?>', $compiled);
        $this->assertStringContainsString('<?php elseif($x === 2): ?>', $compiled);
        $this->assertStringContainsString('<?php elseif($x === 3): ?>', $compiled);
    }

    public function testCompileNestedIf(): void
    {
        $content = '@if($outer) @if($inner) Nested @endif @endif';
        $compiled = $this->directive->compile($content);

        $this->assertEquals(2, substr_count($compiled, '<?php if'));
        $this->assertEquals(2, substr_count($compiled, '<?php endif;'));
    }

    public function testCompileIfWithoutSpaces(): void
    {
        $content = '@if($x)text@endif';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('<?php if($x): ?>', $compiled);
        $this->assertStringContainsString('text', $compiled);
        $this->assertStringContainsString('<?php endif; ?>', $compiled);
    }

    public function testCompileIfWithWhitespace(): void
    {
        $content = '@if  (  $variable  )  content  @endif';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('<?php if(  $variable  ): ?>', $compiled);
    }

    public function testCompileIfWithLogicalOperators(): void
    {
        $content = '@if($a && $b || $c) Content @endif';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('<?php if($a && $b || $c): ?>', $compiled);
    }

    public function testCompileIfWithComparisonOperators(): void
    {
        $content = '@if($count > 10 && $count < 100) Content @endif';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('<?php if($count > 10 && $count < 100): ?>', $compiled);
    }

    public function testCompileIfWithFunctionCall(): void
    {
        $content = '@if(count($items) > 0) Has items @endif';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('<?php if(', $compiled);
        $this->assertStringContainsString('count($items)', $compiled);
        $this->assertStringContainsString('Has items', $compiled);
    }

    public function testCompileIfWithMethodCall(): void
    {
        $content = '@if($user->hasPermission("admin")) Admin @endif';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('<?php if(', $compiled);
        $this->assertStringContainsString('$user->hasPermission("admin")', $compiled);
        $this->assertStringContainsString('Admin', $compiled);
    }

    public function testCompileIfWithNegation(): void
    {
        $content = '@if(!$disabled) Enabled @endif';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('<?php if(!$disabled): ?>', $compiled);
    }

    public function testCompileIfWithTernary(): void
    {
        $content = '@if($active ? true : false) Content @endif';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('<?php if($active ? true : false): ?>', $compiled);
    }

    public function testCompileIfWithNullCoalescing(): void
    {
        $content = '@if($name ?? "guest") Has name @endif';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('<?php if($name ?? "guest"): ?>', $compiled);
    }

    public function testCompileMultipleIfBlocks(): void
    {
        $content = '@if($a) A @endif @if($b) B @endif @if($c) C @endif';
        $compiled = $this->directive->compile($content);

        $this->assertEquals(3, substr_count($compiled, '<?php if'));
        $this->assertEquals(3, substr_count($compiled, '<?php endif;'));
    }

    public function testCompilePreservesContentBetweenDirectives(): void
    {
        $content = 'Before @if($x) Middle @endif After';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('Before', $compiled);
        $this->assertStringContainsString('Middle', $compiled);
        $this->assertStringContainsString('After', $compiled);
    }

    public function testCompileIfWithArrayAccess(): void
    {
        $content = '@if($data["key"]) Content @endif';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('<?php if(', $compiled);
        $this->assertStringContainsString('$data["key"]', $compiled);
        $this->assertStringContainsString('Content', $compiled);
    }

    public function testCompileIfWithIsset(): void
    {
        $content = '@if(isset($variable)) Is set @endif';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('<?php if(', $compiled);
        $this->assertStringContainsString('isset($variable)', $compiled);
        $this->assertStringContainsString('Is set', $compiled);
    }

    public function testCompileIfWithEmpty(): void
    {
        $content = '@if(empty($array)) Is empty @endif';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('<?php if(', $compiled);
        $this->assertStringContainsString('empty($array)', $compiled);
        $this->assertStringContainsString('Is empty', $compiled);
    }
}
