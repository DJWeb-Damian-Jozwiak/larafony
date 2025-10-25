<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\View;

use Larafony\Framework\View\AssetManager;
use Larafony\Framework\View\Contracts\AssetManagerContract;
use PHPUnit\Framework\TestCase;

class AssetManagerTest extends TestCase
{
    private AssetManager $manager;

    protected function setUp(): void
    {
        $this->manager = new AssetManager();
    }

    public function testImplementsAssetManagerContract(): void
    {
        $this->assertInstanceOf(AssetManagerContract::class, $this->manager);
    }

    public function testPushAddsContentToStack(): void
    {
        $this->manager->push('scripts', '<script src="app.js"></script>');

        $rendered = $this->manager->render('scripts');

        $this->assertEquals('<script src="app.js"></script>', $rendered);
    }

    public function testPushMultipleItemsToSameStack(): void
    {
        $this->manager->push('scripts', '<script src="app.js"></script>');
        $this->manager->push('scripts', '<script src="vendor.js"></script>');

        $rendered = $this->manager->render('scripts');

        $expected = "<script src=\"app.js\"></script>\n<script src=\"vendor.js\"></script>";
        $this->assertEquals($expected, $rendered);
    }

    public function testPushToDifferentStacks(): void
    {
        $this->manager->push('scripts', '<script src="app.js"></script>');
        $this->manager->push('styles', '<link href="app.css">');

        $scripts = $this->manager->render('scripts');
        $styles = $this->manager->render('styles');

        $this->assertEquals('<script src="app.js"></script>', $scripts);
        $this->assertEquals('<link href="app.css">', $styles);
    }

    public function testRenderEmptyStackReturnsEmptyString(): void
    {
        $rendered = $this->manager->render('nonexistent');

        $this->assertEquals('', $rendered);
    }

    public function testRenderPreservesOrder(): void
    {
        $this->manager->push('scripts', 'first');
        $this->manager->push('scripts', 'second');
        $this->manager->push('scripts', 'third');

        $rendered = $this->manager->render('scripts');

        $this->assertEquals("first\nsecond\nthird", $rendered);
    }

    public function testPushWithEmptyContent(): void
    {
        $this->manager->push('empty', '');

        $rendered = $this->manager->render('empty');

        $this->assertEquals('', $rendered);
    }

    public function testMultipleStacksAreIndependent(): void
    {
        $this->manager->push('stack1', 'content1');
        $this->manager->push('stack2', 'content2');
        $this->manager->push('stack3', 'content3');

        $this->assertEquals('content1', $this->manager->render('stack1'));
        $this->assertEquals('content2', $this->manager->render('stack2'));
        $this->assertEquals('content3', $this->manager->render('stack3'));
    }

    public function testPushPreservesWhitespace(): void
    {
        $content = "  <script>\n    console.log('test');\n  </script>  ";
        $this->manager->push('scripts', $content);

        $rendered = $this->manager->render('scripts');

        $this->assertEquals($content, $rendered);
    }

    public function testPushHandlesSpecialCharacters(): void
    {
        $content = '<script>alert("Test & <tag>");</script>';
        $this->manager->push('scripts', $content);

        $rendered = $this->manager->render('scripts');

        $this->assertEquals($content, $rendered);
    }

    public function testRenderJoinsWithNewline(): void
    {
        $this->manager->push('items', 'item1');
        $this->manager->push('items', 'item2');

        $rendered = $this->manager->render('items');

        $this->assertStringContainsString("\n", $rendered);
        $this->assertEquals("item1\nitem2", $rendered);
    }

    public function testPushManyItemsToStack(): void
    {
        for ($i = 1; $i <= 100; $i++) {
            $this->manager->push('many', "item{$i}");
        }

        $rendered = $this->manager->render('many');

        $this->assertStringContainsString('item1', $rendered);
        $this->assertStringContainsString('item100', $rendered);
        $this->assertEquals(99, substr_count($rendered, "\n")); // 100 items = 99 newlines
    }

    public function testPushUtf8Content(): void
    {
        $content = '<meta charset="utf-8">Zażółć gęślą jaźń 🚀';
        $this->manager->push('meta', $content);

        $rendered = $this->manager->render('meta');

        $this->assertEquals($content, $rendered);
    }

    public function testRenderSameStackMultipleTimes(): void
    {
        $this->manager->push('scripts', '<script>code</script>');

        $render1 = $this->manager->render('scripts');
        $render2 = $this->manager->render('scripts');

        $this->assertEquals($render1, $render2);
    }

    public function testPushAfterRenderAddsMoreContent(): void
    {
        $this->manager->push('scripts', 'first');
        $first = $this->manager->render('scripts');

        $this->manager->push('scripts', 'second');
        $second = $this->manager->render('scripts');

        $this->assertEquals('first', $first);
        $this->assertEquals("first\nsecond", $second);
    }

    public function testStackNamesAreCaseSensitive(): void
    {
        $this->manager->push('Scripts', 'uppercase');
        $this->manager->push('scripts', 'lowercase');

        $uppercase = $this->manager->render('Scripts');
        $lowercase = $this->manager->render('scripts');

        $this->assertEquals('uppercase', $uppercase);
        $this->assertEquals('lowercase', $lowercase);
    }

    public function testPushWithMultilineContent(): void
    {
        $content = "<script>\n  function test() {\n    return true;\n  }\n</script>";
        $this->manager->push('scripts', $content);

        $rendered = $this->manager->render('scripts');

        $this->assertEquals($content, $rendered);
    }

    public function testRenderMultipleItemsWithMultilineContent(): void
    {
        $this->manager->push('scripts', "line1\nline2");
        $this->manager->push('scripts', "line3\nline4");

        $rendered = $this->manager->render('scripts');

        $this->assertEquals("line1\nline2\nline3\nline4", $rendered);
    }
}
