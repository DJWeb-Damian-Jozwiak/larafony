<?php

declare(strict_types=1);

namespace Larafony\Framework\ErrorHandler;

use ErrorException;
use Larafony\Framework\ErrorHandler\Contracts\ErrorHandler;
use Larafony\Framework\ErrorHandler\Formatters\HtmlErrorFormatter;
use Throwable;

final class DetailedErrorHandler implements ErrorHandler
{
    public function __construct(private readonly HtmlErrorFormatter $formatter = new HtmlErrorFormatter())
    {
    }

    public function handle(Throwable $throwable): void
    {
        http_response_code(500);
        echo $this->formatter->format($throwable);
    }

    public function register(): void
    {
        set_exception_handler($this->handle(...));

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        register_shutdown_function(function (): void {
            // @codeCoverageIgnoreStart
            $error = error_get_last();
            if ($error !== null && $this->isFatalError($error['type'])) {
                $this->handleFatalError($error);
            }
            // @codeCoverageIgnoreEnd
        });
    }

    /**
     * @param array{type: int, message: string, file: string, line: int} $error
     * @codeCoverageIgnore
     */
    private function handleFatalError(array $error): void
    {
        http_response_code(500);
        echo $this->formatter->formatFatalError($error);
    }

    /**
     * @codeCoverageIgnore
     */
    private function isFatalError(int $type): bool
    {
        return in_array($type, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true);
    }
}