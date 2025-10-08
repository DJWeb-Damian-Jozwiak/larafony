<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Clock\Enums\TimeFormat;
use Larafony\Framework\Clock\Enums\Timezone;
use Larafony\Framework\Http\Factories\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class DemoController
{
    public function __construct(
        private readonly ResponseFactory $responseFactory = new ResponseFactory(),
    ) {
    }

    public function home(ServerRequestInterface $request): ResponseInterface
    {
        $currentTime = ClockFactory::timezone(Timezone::EUROPE_WARSAW)
            ->format(TimeFormat::DATETIME);

        $html = <<<HTML
            <!DOCTYPE html>
            <html>
            <head>
                <title>Larafony Demo</title>
                <style>
                    body { font-family: sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
                    h1 { color: #2563eb; }
                    .info { background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0; }
                    ul { list-style: none; padding: 0; }
                    li { margin: 10px 0; }
                    a { color: #2563eb; text-decoration: none; }
                    a:hover { text-decoration: underline; }
                </style>
            </head>
            <body>
                <h1>Larafony Framework Demo</h1>
                <div class="info">
                    <h2>PSR-7/17 Implementation Active</h2>
                    <p><strong>Request Method:</strong> {$request->getMethod()}</p>
                    <p><strong>Request URI:</strong> {$request->getUri()}</p>
                    <p><strong>Protocol:</strong> HTTP/{$request->getProtocolVersion()}</p>
                    <p><strong>Current Time:</strong> {$currentTime}</p>
                </div>
                <p>Error Handler is active. Try these endpoints:</p>
                <ul>
                    <li><a href="/info">📊 View Request Info (JSON)</a></li>
                    <li><a href="/error">⚠️ Trigger E_WARNING</a></li>
                    <li><a href="/exception">💥 Trigger Exception</a></li>
                    <li><a href="/fatal">☠️ Trigger Fatal Error</a></li>
                </ul>
            </body>
            </html>
            HTML;

        return $this->responseFactory->createResponse(200)
            ->withContent($html)
            ->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    public function info(ServerRequestInterface $request): ResponseInterface
    {
        $data = [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'protocol' => 'HTTP/' . $request->getProtocolVersion(),
            'headers' => $request->getHeaders(),
            'query_params' => $request->getQueryParams(),
            'parsed_body' => $request->getParsedBody(),
            'server_params' => array_filter(
                $request->getServerParams(),
                fn($key) => !str_starts_with($key, 'HTTP_'),
                ARRAY_FILTER_USE_KEY,
            ),
        ];

        return $this->responseFactory->createResponse(200)->withJson($data);
    }

    public function handleError(ServerRequestInterface $request): ResponseInterface
    {
        // Trigger a warning
        trigger_error('This is a triggered warning', E_USER_WARNING);

        return $this->responseFactory->createResponse(200)
            ->withContent('Warning triggered - check error handler output')
            ->withHeader('Content-Type', 'text/plain; charset=utf-8');
    }

    public function handleException(ServerRequestInterface $request): ResponseInterface
    {
        throw new RuntimeException('This is a test exception');
    }

    public function handleFatal(ServerRequestInterface $request): ResponseInterface
    {
        // Call undefined function to trigger fatal error
        undefinedFunction();
    }

    public function handleNotFound(ServerRequestInterface $request): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        $html = <<<HTML
            <!DOCTYPE html>
            <html>
            <head>
                <title>404 Not Found</title>
                <style>
                    body { font-family: sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; text-align: center; }
                    h1 { color: #dc2626; }
                    code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; }
                    a { color: #2563eb; text-decoration: none; }
                    a:hover { text-decoration: underline; }
                </style>
            </head>
            <body>
                <h1>404 - Page Not Found</h1>
                <p>The page <code>{$path}</code> does not exist.</p>
                <p><a href="/">← Go back home</a></p>
            </body>
            </html>
            HTML;

        return $this->responseFactory->createResponse(404)
            ->withContent($html)
            ->withHeader('Content-Type', 'text/html; charset=utf-8');
    }
}