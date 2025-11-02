<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\View\Directives;

use Larafony\Framework\View\Directives\ComponentDirective;
use PHPUnit\Framework\TestCase;

class ComponentDirectiveTest extends TestCase
{
    private ComponentDirective $directive;

    protected function setUp(): void
    {
        $this->directive = new ComponentDirective();
    }

    public function testCompileNamedSlot(): void
    {
        $content = '@slot(\'footer\') Footer content @endslot';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('ob_start()', $compiled);
        $this->assertStringContainsString('withNamedSlot', $compiled);
        $this->assertStringContainsString('footer', $compiled);
        $this->assertStringContainsString('Footer content', $compiled);
        $this->assertStringContainsString('ob_get_clean()', $compiled);
    }

    public function testCompileNamedSlotWithDoubleQuotes(): void
    {
        $content = '@slot("header") Header content @endslot';
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('withNamedSlot', $compiled);
        $this->assertStringContainsString('header', $compiled);
        $this->assertStringContainsString('Header content', $compiled);
    }

    public function testCompileMultipleNamedSlots(): void
    {
        $content = '@slot("header") Header @endslot @slot("footer") Footer @endslot';
        $compiled = $this->directive->compile($content);

        $this->assertEquals(2, substr_count($compiled, 'withNamedSlot'));
        $this->assertStringContainsString('header', $compiled);
        $this->assertStringContainsString('footer', $compiled);
    }

    public function testCompileNamedSlotWithMultilineContent(): void
    {
        $content = "@slot('content')
Line 1
Line 2
Line 3
@endslot";
        $compiled = $this->directive->compile($content);

        $this->assertStringContainsString('withNamedSlot', $compiled);
        $this->assertStringContainsString('content', $compiled);
        $this->assertStringContainsString('Line 1', $compiled);
        $this->assertStringContainsString('Line 2', $compiled);
        $this->assertStringContainsString('Line 3', $compiled);
    }

    public function testCompileDoesNotAffectNonComponentTags(): void
    {
        $content = '<div class="test">Normal HTML</div>';
        $compiled = $this->directive->compile($content);

        // Should remain unchanged (no x- prefix)
        $this->assertEquals($content, $compiled);
    }

    public function testFormatComponentName(): void
    {
        $reflection = new \ReflectionClass($this->directive);
        $method = $reflection->getMethod('formatComponentName');

        $this->assertEquals('Alert', $method->invoke($this->directive, 'alert'));
        $this->assertEquals('AlertBox', $method->invoke($this->directive, 'alert-box'));
        $this->assertEquals('StatusBadge', $method->invoke($this->directive, 'status-badge'));
        $this->assertEquals('MyCustomComponent', $method->invoke($this->directive, 'my-custom-component'));
    }

    public function testCastBoolWithBooleanStrings(): void
    {
        $reflection = new \ReflectionClass($this->directive);
        $method = $reflection->getMethod('castBool');

        $this->assertTrue($method->invoke($this->directive, 'true'));
        $this->assertFalse($method->invoke($this->directive, 'false'));
    }

    public function testCastBoolWithNonBooleanStrings(): void
    {
        $reflection = new \ReflectionClass($this->directive);
        $method = $reflection->getMethod('castBool');

        $this->assertEquals('hello', $method->invoke($this->directive, 'hello'));
        $this->assertEquals('123', $method->invoke($this->directive, '123'));
        $this->assertEquals('', $method->invoke($this->directive, ''));
    }

    public function testCastBoolWithNonString(): void
    {
        $reflection = new \ReflectionClass($this->directive);
        $method = $reflection->getMethod('castBool');

        $this->assertEquals(42, $method->invoke($this->directive, 42));
        $this->assertEquals(3.14, $method->invoke($this->directive, 3.14));
        $this->assertNull($method->invoke($this->directive, null));
    }

    public function testParseAttributesSimple(): void
    {
        $reflection = new \ReflectionClass($this->directive);
        $method = $reflection->getMethod('parseAttributes');

        $result = $method->invoke($this->directive, 'title="Hello"');
        $this->assertStringContainsString('title:', $result);
        $this->assertStringContainsString('Hello', $result);
    }

    public function testParseAttributesMultiple(): void
    {
        $reflection = new \ReflectionClass($this->directive);
        $method = $reflection->getMethod('parseAttributes');

        $result = $method->invoke($this->directive, 'title="Hello" class="primary"');
        $this->assertStringContainsString('title:', $result);
        $this->assertStringContainsString('Hello', $result);
        $this->assertStringContainsString('class:', $result);
        $this->assertStringContainsString('primary', $result);
    }

    public function testParseAttributesWithBooleans(): void
    {
        $reflection = new \ReflectionClass($this->directive);
        $method = $reflection->getMethod('parseAttributes');

        $result = $method->invoke($this->directive, 'active="true" disabled="false"');
        $this->assertStringContainsString("active: '1'", $result);
        $this->assertStringContainsString("disabled: ''", $result);
    }

    public function testParseAttributesEmpty(): void
    {
        $reflection = new \ReflectionClass($this->directive);
        $method = $reflection->getMethod('parseAttributes');

        $result = $method->invoke($this->directive, '');
        $this->assertEquals('', $result);
    }
}
