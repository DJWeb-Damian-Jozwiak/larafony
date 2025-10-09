<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Fixtures;

use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Http\Factories\StreamFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class InvocableController
{
    public function __construct(
        private ResponseFactory $responseFactory,
        private StreamFactory $streamFactory
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory->createResponse(200)
            ->withBody($this->streamFactory->createStream('Invocable Response'));
    }
}
