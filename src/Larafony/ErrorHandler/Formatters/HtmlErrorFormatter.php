<?php

declare(strict_types=1);

namespace Larafony\Framework\ErrorHandler\Formatters;

use Throwable;

readonly class HtmlErrorFormatter
{
    public function format(Throwable $throwable): string
    {
        return $this->buildHtml(
            title: '💥 ' . $throwable::class,
            message: $throwable->getMessage(),
            file: $throwable->getFile(),
            line: $throwable->getLine(),
            trace: $throwable->getTrace(),
        );
    }

    /**
     * @param array{type: int, message: string, file: string, line: int, trace?: array<mixed>} $error
     */
    public function formatFatalError(array $error): string
    {
        return $this->buildHtml(
            title: '💥 Fatal Error',
            message: $error['message'],
            file: $error['file'],
            line: $error['line'],
            trace: debug_backtrace(),
        );
    }

    /**
     * @param array<mixed> $trace
     */
    private function buildHtml(
        string $title,
        string $message,
        string $file,
        int $line,
        array $trace,
    ): string {
        $escapedMessage = htmlspecialchars($message);
        $escapedFile = htmlspecialchars($file);
        $traceOutput = $this->formatTrace($trace);

        return $this->renderHtml($title, $escapedMessage, $escapedFile, $line, $traceOutput);
    }

    /**
     * @param array<mixed> $trace
     */
    private function formatTrace(array $trace): string
    {
        ob_start();
        print_r($trace);
        return ob_get_clean();
    }

    private function renderHtml(
        string $title,
        string $message,
        string $file,
        int $line,
        string $trace
    ): string {
        return <<<HTML
            <pre style="background: #1e1e1e; color: #d4d4d4; font-family: monospace; font-size: 14px;">
                <div style="color: #ff6b6b; font-size: 18px; margin-bottom: 10px;">
                    {$title}
                </div>
                <div style="color: #ffd93d; font-size: 16px; margin-bottom: 20px;">
                    {$message}
                </div>
                <div style="color: #6bcf7f; margin-bottom: 20px;">
                    📁 {$file}:{$line}
                </div>
                <div style="color: #a8daff; margin-bottom: 10px;">
                    📚 Backtrace (PHP 8.5):
                </div>
                <div style="color: #c3c3c3;">
                    {$trace}
                </div>
            </pre>
            HTML;
    }
}