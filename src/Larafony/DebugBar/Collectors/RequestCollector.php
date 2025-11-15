<?php

declare(strict_types=1);

namespace Larafony\Framework\DebugBar\Collectors;

use Larafony\Framework\DebugBar\Contracts\DataCollectorContract;
use Psr\Http\Message\ServerRequestInterface;

final class RequestCollector implements DataCollectorContract
{
    private float $startTime;

    public function __construct(
        private readonly ServerRequestInterface $request,
    ) {
        $this->startTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
    }

    public function collect(): array
    {
        $session = [];
        if (isset($_SESSION)) {
            $session = $_SESSION;
        }

        return [
            'method' => $this->request->getMethod(),
            'uri' => (string) $this->request->getUri(),
            'path' => $this->request->getUri()->getPath(),
            'query' => $this->request->getQueryParams(),
            'post' => $this->request->getParsedBody() ?? [],
            'headers' => $this->request->getHeaders(),
            'server' => $this->request->getServerParams(),
            'cookies' => $this->request->getCookieParams(),
            'session' => $session,
            'content_type' => $this->request->getHeaderLine('Content-Type'),
            'ip' => $this->request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $this->request->getHeaderLine('User-Agent'),
            'duration' => round((microtime(true) - $this->startTime) * 1000, 2),
        ];
    }

    public function getName(): string
    {
        return 'request';
    }
}
