<?php

declare(strict_types=1);

namespace Larafony\Framework\ErrorHandler;

use ErrorException;
use Larafony\Framework\Core\Exceptions\NotFoundError;
use Larafony\Framework\ErrorHandler\Contracts\ErrorHandler;
use Larafony\Framework\View\ViewManager;
use Throwable;

final class DetailedErrorHandler implements ErrorHandler
{
    public function __construct(
        private readonly ViewManager $viewManager,
        private readonly bool $debug = false
    ) {
    }

    public function handle(Throwable $throwable): void
    {
        $statusCode = $this->getStatusCode($throwable);
        http_response_code($statusCode);

        try {
            if ($this->debug) {
                echo $this->renderDebugView($throwable);
            } else {
                echo $this->renderProductionView($statusCode);
            }
        } catch (\Throwable $e) {
            // Fallback if view rendering fails
            echo $this->renderFallback($statusCode, $throwable, $e);
        }
    }

    public function register(): void
    {
        set_exception_handler($this->handle(...));

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        register_shutdown_function(function (): void {
            $error = error_get_last();

            if ($error !== null && $this->isFatalError($error['type'])) {
                $this->handleFatalError($error);
            }
        });
    }

    /**
     * @param array{type: int, message: string, file: string, line: int} $error
     */
    private function handleFatalError(array $error): void
    {
        http_response_code(500);
        $exception = new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
        echo $this->renderDebugView($exception);
    }

    private function isFatalError(int $type): bool
    {
        return in_array($type, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true);
    }

    private function renderDebugView(Throwable $exception): string
    {
        $backtrace = new Backtrace();
        $trace = $backtrace->generate($exception);

        $frames = array_map(function ($frame) {
            return [
                'file' => $frame->file,
                'line' => $frame->line,
                'class' => $frame->class,
                'function' => $frame->function,
                'snippet' => [
                    'lines' => $frame->snippet->lines,
                    'errorLine' => $frame->snippet->errorLine,
                ],
            ];
        }, $trace->frames);

        return $this->viewManager->make('errors.debug', [
            'exception' => [
                'class' => $exception::class,
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ],
            'backtrace' => $frames,
        ])->render()->getBody()->__toString();
    }

    private function renderProductionView(int $statusCode): string
    {
        $view = match ($statusCode) {
            404 => 'errors.404',
            default => 'errors.500'
        };

        return $this->viewManager->make($view)->render()->getBody()->__toString();
    }

    private function getStatusCode(Throwable $exception): int
    {
        return match (true) {
            $exception instanceof NotFoundError => 404,
            default => 500
        };
    }

    private function renderFallback(int $statusCode, Throwable $original, Throwable $renderError): string
    {
        $title = $statusCode === 404 ? '404 Not Found' : '500 Internal Server Error';
        $message = $statusCode === 404
            ? 'The requested page could not be found.'
            : 'An error occurred while processing your request.';

        if ($this->debug) {
            return sprintf(
                '<h1>%s</h1><p>%s</p><hr><h2>Original Error:</h2><pre>%s</pre><hr><h2>Render Error:</h2><pre>%s</pre>',
                htmlspecialchars($title),
                htmlspecialchars($message),
                htmlspecialchars($original->__toString()),
                htmlspecialchars($renderError->__toString())
            );
        }

        return sprintf(
            '<h1>%s</h1><p>%s</p>',
            htmlspecialchars($title),
            htmlspecialchars($message)
        );
    }
}
