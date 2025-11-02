<?php

declare(strict_types=1);

namespace Larafony\Framework\ErrorHandler;

use Throwable;

final class FallbackErrorHandler extends DetailedErrorHandler
{
    public function handle(Throwable $throwable): void
    {
        try {
            parent::handle($throwable);
        } catch (Throwable $e) {
            $code = http_response_code();
            $statusCode = is_int($code) ? $code : 500;
            echo $this->renderFallback($statusCode, $throwable, $e);
        }
    }

    private function renderFallback(int $statusCode, Throwable $original, Throwable $renderError): string
    {
        $title = 'Server Error';
        $message = 'An error occurred while processing your request.';

        if ($this->debug) {
            return sprintf(
                '<h1>%s</h1>
<p>%s</p><hr><h2>Original Error:</h2><pre>%s</pre><hr><h2>Render Error:</h2><pre>%s</pre>',
                htmlspecialchars($title),
                htmlspecialchars($message),
                htmlspecialchars($original->__toString()),
                htmlspecialchars($renderError->__toString()),
            );
        }

        return sprintf(
            '<h1>%s</h1><p>%s</p>',
            htmlspecialchars($title),
            htmlspecialchars($message),
        );
    }
}
