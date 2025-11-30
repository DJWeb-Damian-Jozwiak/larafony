<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Commands;

use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Command;
use Larafony\Framework\Console\Commands\ConnectionHelpers\UserInputHandler;
use Larafony\Framework\Core\EnvFileHandler;
use Larafony\Framework\Database\DatabaseManager;

#[AsCommand('database:connect')]
class ConnectToDatabase extends Command
{
    /** @var array<string, string> */
    private array $defaults = [
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'larafony',
        'username' => 'root',
        'password' => '',
    ];
    public function run(): int
    {
        $config = $this->container->get(ConfigContract::class);
        $params = (array) $config->get('database.connections');
        try {
            new DatabaseManager($params)->connection()->connect();
            $this->output->success('Already connected to database');
            return 0; // No changes needed
        } catch (\Throwable $e) {
            $this->tryConnect();
            return 2;// credentials updated, reload needed
        }
    }

    /**
     * @param array<string, array<string, mixed>> $params
     * @param $connection
     *
     * @return true
     */
    public function baseConnect(array $params, string $connection): bool
    {
        foreach ($this->defaults as $key => $default) {
            $value = new UserInputHandler()->handle($key, $this->output, $default);
            // Update env with DB_ prefix and params
            $envKey = 'DB_' . strtoupper($key);
            new EnvFileHandler()->update($envKey, $value);
            $params[$connection][$key] = $value;
        }

        // Test connection
        new DatabaseManager($params)->connection()->connect();
        $this->output->success('Database connected successfully!');
        return true;
    }

    private function tryConnect(): bool
    {
        $this->output->warning('Configure database connection');
        $connected = false;
        $config = $this->container->get(ConfigContract::class);
        $connection = $config->get('database.default');
        $params = (array) $config->get('database.connections');

        do {
            try {
                $connected = $this->baseConnect($params, $connection);
            } catch (\PDOException $e) {
                $this->output->error("Connection failed: {$e->getMessage()}");
                $this->output->info('Please try again with correct credentials...');
            }
        } while (! $connected);

        return true; // Credentials were updated
    }
}
