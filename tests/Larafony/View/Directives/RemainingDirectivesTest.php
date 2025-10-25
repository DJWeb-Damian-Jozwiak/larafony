<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\View\Directives;

use Larafony\Framework\View\Directives\DoWhileDirective;
use Larafony\Framework\View\Directives\EmptyDirective;
use Larafony\Framework\View\Directives\ExtendDirective;
use Larafony\Framework\View\Directives\ForDirective;
use Larafony\Framework\View\Directives\IssetDirective;
use Larafony\Framework\View\Directives\StackDirective;
use Larafony\Framework\View\Directives\SwitchDirective;
use Larafony\Framework\View\Directives\UnlessDirective;
use Larafony\Framework\View\Directives\WhileDirective;
use Larafony\Framework\View\Directives\YieldDirective;
use PHPUnit\Framework\TestCase;

class RemainingDirectivesTest extends TestCase
{
    public function testUnlessDirective(): void
    {
        $directive = new UnlessDirective();
        $content = '@unless($hidden) Visible @endunless';

        $compiled = $directive->compile($content);

        $this->assertStringContainsString('<?php if(!', $compiled);
        $this->assertStringContainsString('$hidden', $compiled);
        $this->assertStringContainsString('Visible', $compiled);
    }

    public function testWhileDirective(): void
    {
        $directive = new WhileDirective();
        $content = '@while($count > 0) Decrement @endwhile';

        $compiled = $directive->compile($content);

        $this->assertStringContainsString('<?php while(', $compiled);
        $this->assertStringContainsString('$count > 0', $compiled);
        $this->assertStringContainsString('<?php endwhile;', $compiled);
    }

    public function testForDirective(): void
    {
        $directive = new ForDirective();
        $content = '@for($i = 0; $i < 10; $i++) Number @endfor';

        $compiled = $directive->compile($content);

        $this->assertStringContainsString('<?php for(', $compiled);
        $this->assertStringContainsString('$i = 0', $compiled);
        $this->assertStringContainsString('<?php endfor;', $compiled);
    }

    public function testDoWhileDirective(): void
    {
        $directive = new DoWhileDirective();
        $content = '@do Execute @dowhile($condition)';

        $compiled = $directive->compile($content);

        $this->assertStringContainsString('<?php do {', $compiled);
        $this->assertStringContainsString('Execute', $compiled);
        $this->assertStringContainsString('while($condition)', $compiled);
    }

    public function testIssetDirective(): void
    {
        $directive = new IssetDirective();
        $content = '@isset($variable) Variable is set @endisset';

        $compiled = $directive->compile($content);

        $this->assertStringContainsString('<?php if(isset(', $compiled);
        $this->assertStringContainsString('$variable', $compiled);
        $this->assertStringContainsString('Variable is set', $compiled);
    }

    public function testEmptyDirective(): void
    {
        $directive = new EmptyDirective();
        $content = '@empty($array) Array is empty @endempty';

        $compiled = $directive->compile($content);

        $this->assertStringContainsString('<?php if(empty(', $compiled);
        $this->assertStringContainsString('$array', $compiled);
        $this->assertStringContainsString('Array is empty', $compiled);
    }

    public function testSwitchDirective(): void
    {
        $directive = new SwitchDirective();
        $content = '@switch($value) @case(1) One @break @case(2) Two @break @default Default @endswitch';

        $compiled = $directive->compile($content);

        $this->assertStringContainsString('<?php switch(', $compiled);
        $this->assertStringContainsString('$value', $compiled);
        $this->assertStringContainsString('<?php case 1:', $compiled);
        $this->assertStringContainsString('<?php break;', $compiled);
        $this->assertStringContainsString('<?php default:', $compiled);
        $this->assertStringContainsString('<?php endswitch;', $compiled);
    }

    public function testExtendDirective(): void
    {
        $directive = new ExtendDirective();
        $content = '@extends("layouts.app") Content here';

        $compiled = $directive->compile($content);

        $this->assertStringContainsString('$this->extend(', $compiled);
        $this->assertStringContainsString('layouts.app', $compiled);
    }

    public function testYieldDirective(): void
    {
        $directive = new YieldDirective();
        $content = 'Header @yield("content") Footer';

        $compiled = $directive->compile($content);

        $this->assertStringContainsString('<?php echo $this->yield(', $compiled);
        $this->assertStringContainsString('content', $compiled);
    }

    public function testStackDirective(): void
    {
        $directive = new StackDirective();
        $content = '@push("scripts") <script></script> @endpush Then @stack("scripts")';

        $compiled = $directive->compile($content);

        $this->assertStringContainsString('pushToStack', $compiled);
        $this->assertStringContainsString('scripts', $compiled);
        $this->assertStringContainsString('renderStack', $compiled);
    }

}
