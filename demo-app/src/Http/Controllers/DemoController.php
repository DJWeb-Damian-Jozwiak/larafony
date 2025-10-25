<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Clock\Enums\TimeFormat;
use Larafony\Framework\Clock\Enums\Timezone;
use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Web\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class DemoController extends Controller
{
    public function __construct(
        private readonly ResponseFactory $responseFactory = new ResponseFactory(),
    ) {
        parent::__construct(\Larafony\Framework\Web\Application::instance());
    }

    public function home(ServerRequestInterface $request): ResponseInterface
    {
        $currentTime = ClockFactory::timezone(Timezone::EUROPE_WARSAW)
            ->format(TimeFormat::DATETIME);

        return $this->render('home', [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'protocol' => $request->getProtocolVersion(),
            'currentTime' => $currentTime,
        ]);
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
                static fn ($key) => ! str_starts_with($key, 'HTTP_'),
                ARRAY_FILTER_USE_KEY,
            ),
        ];

        return $this->json($data);
    }
}
