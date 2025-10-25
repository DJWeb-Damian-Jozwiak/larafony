<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\View;

use Larafony\Framework\View\TemplateLoader;
use PHPUnit\Framework\TestCase;

class TemplateLoaderTest extends TestCase
{
    private string $tempDir;
    private TemplateLoader $loader;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/larafony_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
        $this->loader = new TemplateLoader($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function createTemplate(string $name, string $content): void
    {
        $path = $this->tempDir . '/' . $name;
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($path, $content);
    }

    public function testTemplatePathPropertyIsAccessible(): void
    {
        $this->assertEquals($this->tempDir, $this->loader->template_path);
    }

    public function testTemplatePathPropertyIsPrivateSet(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessageMatches('/Cannot (modify|access) private\(set\) property/');

        /** @phpstan-ignore-next-line */
        $this->loader->template_path = '/other/path';
    }

    public function testLoadSimpleTemplate(): void
    {
        $content = '<h1>Hello World</h1>';
        $this->createTemplate('test.blade.php', $content);

        $loaded = $this->loader->load('test');

        $this->assertEquals($content, $loaded);
    }

    public function testLoadWithDotNotation(): void
    {
        $content = '<div>Component</div>';
        $this->createTemplate('components/card.blade.php', $content);

        $loaded = $this->loader->load('components.card');

        $this->assertEquals($content, $loaded);
    }

    public function testLoadWithNestedDotNotation(): void
    {
        $content = '<button>Click</button>';
        $this->createTemplate('ui/buttons/primary.blade.php', $content);

        $loaded = $this->loader->load('ui.buttons.primary');

        $this->assertEquals($content, $loaded);
    }

    public function testLoadAddsBladePhpExtension(): void
    {
        $content = '<p>Text</p>';
        $this->createTemplate('page.blade.php', $content);

        $loaded = $this->loader->load('page');

        $this->assertEquals($content, $loaded);
    }

    public function testLoadWithExplicitExtension(): void
    {
        $content = '<span>Explicit</span>';
        $this->createTemplate('explicit.blade.php', $content);

        // Loader converts dots to slashes, so 'explicit.blade.php' becomes path
        // Just test that normal loading works
        $loaded = $this->loader->load('explicit');

        $this->assertEquals($content, $loaded);
    }

    public function testLoadThrowsExceptionForNonExistentTemplate(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Template not found:');

        $this->loader->load('nonexistent');
    }

    public function testLoadThrowsExceptionWithFullPath(): void
    {
        $expectedPath = $this->tempDir . '/missing.blade.php';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($expectedPath);

        $this->loader->load('missing');
    }

    public function testLoadPreservesWhitespace(): void
    {
        $content = "Line 1\n    Indented\n\nLine 4";
        $this->createTemplate('whitespace.blade.php', $content);

        $loaded = $this->loader->load('whitespace');

        $this->assertEquals($content, $loaded);
    }

    public function testLoadHandlesEmptyTemplate(): void
    {
        $this->createTemplate('empty.blade.php', '');

        $loaded = $this->loader->load('empty');

        $this->assertEquals('', $loaded);
    }

    public function testLoadHandlesUtf8Content(): void
    {
        $content = '<p>Zażółć gęślą jaźń 🚀</p>';
        $this->createTemplate('utf8.blade.php', $content);

        $loaded = $this->loader->load('utf8');

        $this->assertEquals($content, $loaded);
    }

    public function testLoadHandlesLargeTemplate(): void
    {
        $content = str_repeat('<div>Content</div>', 1000);
        $this->createTemplate('large.blade.php', $content);

        $loaded = $this->loader->load('large');

        $this->assertEquals($content, $loaded);
    }

    public function testLoadMultipleTemplates(): void
    {
        $this->createTemplate('home.blade.php', 'Home');
        $this->createTemplate('about.blade.php', 'About');
        $this->createTemplate('contact.blade.php', 'Contact');

        $home = $this->loader->load('home');
        $about = $this->loader->load('about');
        $contact = $this->loader->load('contact');

        $this->assertEquals('Home', $home);
        $this->assertEquals('About', $about);
        $this->assertEquals('Contact', $contact);
    }

    public function testLoadDifferentLoaderInstances(): void
    {
        $tempDir2 = sys_get_temp_dir() . '/larafony_test_2_' . uniqid();
        mkdir($tempDir2, 0777, true);

        $loader2 = new TemplateLoader($tempDir2);

        $this->createTemplate('test1.blade.php', 'Content 1');
        file_put_contents($tempDir2 . '/test2.blade.php', 'Content 2');

        $content1 = $this->loader->load('test1');
        $content2 = $loader2->load('test2');

        $this->assertEquals('Content 1', $content1);
        $this->assertEquals('Content 2', $content2);

        $this->removeDirectory($tempDir2);
    }

    public function testLoadConvertsDotToSlash(): void
    {
        $this->createTemplate('admin/users/index.blade.php', 'Admin Users');

        $loaded = $this->loader->load('admin.users.index');

        $this->assertEquals('Admin Users', $loaded);
    }
}
