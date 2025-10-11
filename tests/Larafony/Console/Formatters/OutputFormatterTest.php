<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Larafony\Console\Formatters;

use Larafony\Framework\Console\Formatters\OutputFormatter;
use Larafony\Framework\Console\Formatters\Styles\DangerStyle;
use Larafony\Framework\Console\Formatters\Styles\InfoStyle;
use Larafony\Framework\Console\Formatters\Styles\SuccessStyle;
use Larafony\Framework\Console\Formatters\Styles\WarningStyle;
use Larafony\Framework\Web\Application;
use PHPUnit\Framework\TestCase;

final class OutputFormatterTest extends TestCase
{
    private OutputFormatter $formatter;

    protected function setUp(): void
    {
        $container = Application::instance();
        $this->formatter = new OutputFormatter($container);
    }

    public function testFormatsDangerStyle(): void
    {
        $this->formatter->withStyle('danger', new DangerStyle());

        $result = $this->formatter->format('<danger>Error message</danger>');

        $this->assertStringContainsString("\033[31m", $result);
        $this->assertStringContainsString('Error message', $result);
        $this->assertStringContainsString("\033[0m", $result);
    }

    public function testFormatsInfoStyle(): void
    {
        $this->formatter->withStyle('info', new InfoStyle());

        $result = $this->formatter->format('<info>Info message</info>');

        $this->assertStringContainsString("\033[36m", $result);
        $this->assertStringContainsString('Info message', $result);
    }

    public function testFormatsSuccessStyle(): void
    {
        $this->formatter->withStyle('success', new SuccessStyle());

        $result = $this->formatter->format('<success>Success message</success>');

        $this->assertStringContainsString("\033[32m", $result);
        $this->assertStringContainsString('Success message', $result);
    }

    public function testFormatsWarningStyle(): void
    {
        $this->formatter->withStyle('warning', new WarningStyle());

        $result = $this->formatter->format('<warning>Warning message</warning>');

        $this->assertStringContainsString("\033[33m", $result);
        $this->assertStringContainsString('Warning message', $result);
    }

    public function testReturnsPlainTextForUnknownStyle(): void
    {
        $result = $this->formatter->format('<unknown>Plain text</unknown>');

        $this->assertSame('Plain text', $result);
    }

    public function testFormatsMultipleStylesInOneMessage(): void
    {
        $this->formatter->withStyle('danger', new DangerStyle());
        $this->formatter->withStyle('success', new SuccessStyle());

        $result = $this->formatter->format('<danger>Error</danger> and <success>Success</success>');

        $this->assertStringContainsString("\033[31m", $result);
        $this->assertStringContainsString("\033[32m", $result);
        $this->assertStringContainsString('Error', $result);
        $this->assertStringContainsString('Success', $result);
    }

    public function testReturnsUnformattedTextWithoutTags(): void
    {
        $result = $this->formatter->format('Plain text without tags');

        $this->assertSame('Plain text without tags', $result);
    }
}
