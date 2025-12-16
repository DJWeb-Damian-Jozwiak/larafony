<?php

declare(strict_types=1);

namespace Larafony\Framework\MCP\Http;

use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\MCP\Contracts\McpServerFactoryContract;
use Larafony\Framework\Routing\Advanced\Attributes\Route;
use Larafony\Framework\Web\Controller;
use Mcp\Server\Transport\StreamableHttpTransport;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * HTTP controller for MCP (Model Context Protocol) endpoint.
 *
 * Exposes the MCP server via HTTP/SSE transport, allowing remote
 * AI assistants to connect to the application.
 */
class McpController extends Controller
{
    #[Route('/mcp', ['POST', 'OPTIONS', 'DELETE'])]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $config = $this->container->get(ConfigContract::class);

        $name = $config->get('mcp.name', $config->get('app.name', 'Larafony MCP Server'));
        $version = $config->get('mcp.version', '1.0.0');
        $instructions = $config->get('mcp.instructions');
        $discoveryPath = $config->get('mcp.discovery.path');

        $factory = $this->container->get(McpServerFactoryContract::class);

        $server = $factory->create(
            name: $name,
            version: $version,
            instructions: $instructions,
            discoveryPath: $discoveryPath,
        );

        $transport = new StreamableHttpTransport(
            request: $request,
            responseFactory: $this->container->get(ResponseFactoryInterface::class),
            streamFactory: $this->container->get(StreamFactoryInterface::class),
        );

        return $server->run($transport);
    }
}
