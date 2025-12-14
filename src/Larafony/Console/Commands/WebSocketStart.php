<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Commands;

use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Attributes\CommandOption;
use Larafony\Framework\Console\Command;
use Larafony\Framework\WebSockets\Contracts\ServerContract;

#[AsCommand(name: 'websocket:start')]
class WebSocketStart extends Command
{
    #[CommandOption(name: 'host', description: 'The host to bind to (overrides config)')]
    protected ?string $host = null;

    #[CommandOption(name: 'port', description: 'The port to listen on (overrides config)')]
    protected ?int $port = null;

    public function run(): int
    {
        $server = $this->container->get(ServerContract::class);

        $host = $this->host ?? '0.0.0.0';
        $port = $this->port ?? 8080;

        $this->output->info("Starting WebSocket server on {$host}:{$port}...");
        $this->registerLoggingHandlers($server);
        $this->output->success("WebSocket server listening on ws://{$host}:{$port}");
        $this->output->writeln('Press Ctrl+C to stop the server');

        try {
            $server->run();
        } catch (\Throwable $e) {
            $this->output->error('WebSocket server error: ' . $e->getMessage());

            return 1;
        }

        return 0;
    }

    private function registerLoggingHandlers(ServerContract $server): void
    {
        $server->on('open', function ($data, $connection): void {
            $this->output->writeln("[{$connection->getId()}] Connected from {$connection->getRemoteAddress()}");
        });

        $server->on('close', function ($data, $connection): void {
            $this->output->writeln("[{$connection->getId()}] Disconnected");
        });

        $server->on('error', function ($error, $connection): void {
            $message = $error instanceof \Throwable ? $error->getMessage() : (string) $error;
            $this->output->error("[{$connection->getId()}] Error: {$message}");
        });
    }
}
