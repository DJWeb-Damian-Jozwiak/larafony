<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\View\Engines;

use Larafony\Framework\View\Contracts\RendererContract;
use Larafony\Framework\View\Engines\BladeAdapter;
use PHPUnit\Framework\TestCase;

class BladeAdapterTest extends TestCase
{
    private string $tempDir;
    private string $cacheDir;
    private BladeAdapter $adapter;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/larafony_blade_test_' . uniqid();
        $this->cacheDir = $this->tempDir . '/cache';
        mkdir($this->tempDir, 0777, true);
        mkdir($this->cacheDir, 0777, true);

        $this->adapter = new BladeAdapter($this->tempDir, $this->cacheDir);
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
        file_put_contents($this->tempDir . '/' . $name, $content);
    }

    public function testImplementsRendererContract(): void
    {
        $this->assertInstanceOf(RendererContract::class, $this->adapter);
    }

    public function testRenderSimpleTemplate(): void
    {
        $this->createTemplate('test.blade.php', '<h1>Hello World</h1>');

        $result = $this->adapter->render('test', []);

        $this->assertEquals('<h1>Hello World</h1>', $result);
    }

    public function testRenderWithData(): void
    {
        $this->createTemplate('greeting.blade.php', '<p>Hello {{ $name }}</p>');

        $result = $this->adapter->render('greeting', ['name' => 'John']);

        $this->assertStringContainsString('Hello', $result);
        $this->assertStringContainsString('John', $result);
    }

    public function testRenderEscapesHtmlInEcho(): void
    {
        $this->createTemplate('xss.blade.php', '{{ $content }}');

        $result = $this->adapter->render('xss', ['content' => '<script>alert("XSS")</script>']);

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    public function testRenderRawEchoDoesNotEscape(): void
    {
        $this->createTemplate('raw.blade.php', '{!! $html !!}');

        $result = $this->adapter->render('raw', ['html' => '<strong>Bold</strong>']);

        $this->assertStringContainsString('<strong>Bold</strong>', $result);
    }

    public function testRenderCreatesCache(): void
    {
        $this->createTemplate('cached.blade.php', '<div>Cached Content</div>');

        $result = $this->adapter->render('cached', []);

        $this->assertStringContainsString('Cached Content', $result);

        // Check that cache file was created
        $cacheFiles = glob($this->cacheDir . '/*.php');
        $this->assertGreaterThan(0, count($cacheFiles));
    }

    public function testRenderIfDirective(): void
    {
        $this->createTemplate('if.blade.php', '@if($show) Visible @endif');

        $resultTrue = $this->adapter->render('if', ['show' => true]);
        $resultFalse = $this->adapter->render('if', ['show' => false]);

        $this->assertStringContainsString('Visible', $resultTrue);
        $this->assertStringNotContainsString('Visible', $resultFalse);
    }

    public function testRenderSimpleLoop(): void
    {
        $template = 'Items: {{ implode(",", $items) }}';
        $this->createTemplate('loop.blade.php', $template);

        $result = $this->adapter->render('loop', ['items' => ['A', 'B', 'C']]);

        $this->assertStringContainsString('Items:', $result);
        $this->assertStringContainsString('A,B,C', $result);
    }

    public function testPushToStackAndRender(): void
    {
        $this->adapter->pushToStack('scripts', '<script src="app.js"></script>');
        $this->adapter->pushToStack('scripts', '<script src="vendor.js"></script>');

        $result = $this->adapter->renderStack('scripts');

        $this->assertStringContainsString('app.js', $result);
        $this->assertStringContainsString('vendor.js', $result);
    }

    public function testSectionAndYield(): void
    {
        $this->adapter->section('content');
        echo 'Section Content';
        $this->adapter->endSection();

        $result = $this->adapter->yield('content');

        $this->assertEquals('Section Content', $result);
    }

    public function testYieldNonExistentSection(): void
    {
        $result = $this->adapter->yield('nonexistent');

        $this->assertEquals('', $result);
    }

    public function testLayoutWithNamedSlots(): void
    {
        // Create layout with named slots
        $layout = <<<'BLADE'
<!DOCTYPE html>
<html>
<head>
    <title>@yield('title')</title>
    @stack('styles')
</head>
<body>
    <header>
        @yield('header')
    </header>

    <main>
        @yield('content')
    </main>

    <footer>
        @yield('footer')
    </footer>

    @stack('scripts')
</body>
</html>
BLADE;

        // Create page that extends layout with sections and stacks
        $page = <<<'BLADE'
@extends('layout')

@section('title')
My Page Title
@endsection

@section('header')
<h1>Welcome to My Site</h1>
@endsection

@section('content')
<p>This is the main content {{ $message }}</p>
@if($showExtra)
<div>Extra content</div>
@endif
@endsection

@section('footer')
<p>&copy; 2024</p>
@endsection

@push('styles')
<link rel="stylesheet" href="app.css">
@endpush

@push('scripts')
<script src="app.js"></script>
@endpush
BLADE;

        $this->createTemplate('layout.blade.php', $layout);
        $this->createTemplate('page.blade.php', $page);

        $result = $this->adapter->render('page', [
            'message' => 'Hello World',
            'showExtra' => true
        ]);

        // Verify layout structure
        $this->assertStringContainsString('<!DOCTYPE html>', $result);
        $this->assertStringContainsString('<html>', $result);
        $this->assertStringContainsString('<head>', $result);
        $this->assertStringContainsString('<body>', $result);

        // Verify sections rendered
        $this->assertStringContainsString('My Page Title', $result);
        $this->assertStringContainsString('Welcome to My Site', $result);
        $this->assertStringContainsString('This is the main content Hello World', $result);
        $this->assertStringContainsString('Extra content', $result);
        $this->assertStringContainsString('&copy; 2024', $result);

        // Verify stacks rendered
        $this->assertStringContainsString('<link rel="stylesheet" href="app.css">', $result);
        $this->assertStringContainsString('<script src="app.js"></script>', $result);
    }

    public function testMultipleInstancesOfSameTemplateRender(): void
    {
        // Create a simple template that will be rendered multiple times
        $template = <<<'BLADE'
<span class="badge">{{ $text }}</span>
BLADE;

        $this->createTemplate('badge.blade.php', $template);

        // Render the same template 5 times with different data
        $result1 = $this->adapter->render('badge', ['text' => 'First']);
        $result2 = $this->adapter->render('badge', ['text' => 'Second']);
        $result3 = $this->adapter->render('badge', ['text' => 'Third']);
        $result4 = $this->adapter->render('badge', ['text' => 'Fourth']);
        $result5 = $this->adapter->render('badge', ['text' => 'Fifth']);

        // Verify each render has the correct content
        $this->assertStringContainsString('First', $result1);
        $this->assertStringContainsString('Second', $result2);
        $this->assertStringContainsString('Third', $result3);
        $this->assertStringContainsString('Fourth', $result4);
        $this->assertStringContainsString('Fifth', $result5);

        // Verify none are empty
        $this->assertNotEmpty($result1);
        $this->assertNotEmpty($result2);
        $this->assertNotEmpty($result3);
        $this->assertNotEmpty($result4);
        $this->assertNotEmpty($result5);
    }

}
