<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\ErrorHandler\Formatters;

use Larafony\Framework\ErrorHandler\Formatters\HtmlErrorFormatter;
use PHPUnit\Framework\TestCase;

final class HtmlErrorFormatterTest extends TestCase
{
    public function testFormatOutputsExceptionDetails(): void
    {
        $formatter = new HtmlErrorFormatter();
        $exception = new \Exception('Test error', 123);

        $html = $formatter->format($exception);

        $this->assertStringContainsString('Test error', $html);
        $this->assertStringContainsString('Exception', $html);
        $this->assertStringContainsString('💥', $html);
    }

    public function testFormatOutputsFileAndLine(): void
    {
        $formatter = new HtmlErrorFormatter();
        $exception = new \RuntimeException('Runtime test');

        $html = $formatter->format($exception);

        $this->assertStringContainsString($exception->getFile(), $html);
        $this->assertStringContainsString((string)$exception->getLine(), $html);
    }

    public function testFormatFatalErrorOutputs(): void
    {
        $formatter = new HtmlErrorFormatter();
        $error = [
            'type' => E_ERROR,
            'message' => 'Fatal error occurred',
            'file' => __FILE__,
            'line' => 42
        ];

        $html = $formatter->formatFatalError($error);

        $this->assertStringContainsString('Fatal error occurred', $html);
        $this->assertStringContainsString(__FILE__, $html);
        $this->assertStringContainsString('42', $html);
        $this->assertStringContainsString('Fatal Error', $html);
    }

    public function testFormatIncludesBacktrace(): void
    {
        $formatter = new HtmlErrorFormatter();
        $exception = new \Exception('Backtrace test');

        $html = $formatter->format($exception);

        $this->assertStringContainsString('Backtrace', $html);
    }
}
