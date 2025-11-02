<?php

declare(strict_types=1);

namespace Larafony\Framework\ErrorHandler;

use Larafony\Framework\Exceptions\FatalError;
use ErrorException;
use Throwable;

abstract class BaseHandler
{
    public function register(): void
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleFatalError']);
    }

    public function unregister(): void
    {
        restore_error_handler();
        restore_exception_handler();
    }

    public abstract function handleException(Throwable $exception): void;

    public function handleError(
        int $level,
        string $message,
        string $file = '',
        int $line = 0
    ): bool
    {
        if (! (error_reporting() & $level)) {
            return false;

        }

        throw new ErrorException($message, 0, $level, $file, $line);
    }

    public function handleFatalError(): void
    {
        $error = error_get_last();
        if ($error !== null && $this->isFatalError($error['type'])) {
            $this->handleException(
                new FatalError(
                    $error['message'],
                    0,
                    $error['type'],
                    $error['file'],
                    $error['line']
                )
            );

        }
    }

    private function isFatalError(int $type): bool
    {
        return in_array($type, [
            E_ERROR,
            E_PARSE,
            E_CORE_ERROR,
            E_COMPILE_ERROR,
            E_USER_ERROR,
            E_USER_DEPRECATED,
        ], true);
    }
}
