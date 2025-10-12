<?php

declare(strict_types=1);

namespace Larafony\Framework\Database;

use Larafony\Framework\Database\Base\Contracts\ConnectionContract;
use Larafony\Framework\Database\Base\Schema\SchemaBuilder;
use Larafony\Framework\Database\Drivers\MySQL\Schema\Connection;
use Larafony\Framework\Database\Drivers\MySQL\SchemaBuilder as MySQLSchemaBuilder;

class DatabaseManager
{
    public protected(set) string $defaultConnection = 'mysql';
    /** @var array<string, ConnectionContract> */
    protected array $connections = [];

    /** @var array<string, SchemaBuilder> */
    protected array $schemaBuilders = [];

    /**
     * @param array<string, array<string, mixed>> $config
     */
    public function __construct(protected array $config = [])
    {
    }

    /**
     * Get a database connection instance.
     */
    public function connection(?string $name = null): ConnectionContract
    {
        $name = $name ?? $this->defaultConnection;

        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        $connection = $this->makeConnection($name);

        $this->connections[$name] = $connection;

        return $connection;
    }

    /**
     * Get a schema builder instance.
     */
    public function schema(?string $name = null): SchemaBuilder
    {
        $name = $name ?? $this->defaultConnection;

        if (isset($this->schemaBuilders[$name])) {
            return $this->schemaBuilders[$name];
        }

        $connection = $this->connection($name);

        $schemaBuilder = new MySQLSchemaBuilder($connection);

        $this->schemaBuilders[$name] = $schemaBuilder;

        return $schemaBuilder;
    }

    public function defaultConnection(string $name): self
    {
        return clone($this, ['defaultConnection' => $name]);
    }

    protected function makeConnection(string $name): ConnectionContract
    {
        $config = $this->getConfig($name);

        $driver = $config['driver'] ?? 'mysql';

        return match ($driver) {
            'mysql' => $this->createMySQLConnection($config),
            default => throw new \InvalidArgumentException("Unsupported driver: {$driver}"),
        };
    }

    /**
     * Create MySQL connection.
     *
     * @param array<string, mixed> $config
     */
    protected static function createMySQLConnection(array $config): ConnectionContract
    {
        $connection = new Connection(
            host: $config['host'] ?? 'localhost',
            port: $config['port'] ?? 3306,
            database: $config['database'] ?? null,
            username: $config['username'] ?? 'root',
            password: $config['password'] ?? '',
            charset: $config['charset'] ?? 'utf8mb4',
        );

        $connection->connect();

        return $connection;
    }

    /**
     * Get connection configuration.
     *
     * @return array<string, mixed>
     */
    protected function getConfig(string $name): array
    {
        if (! isset($this->config[$name])) {
            throw new \InvalidArgumentException("Database connection [{$name}] not configured.");
        }

        return $this->config[$name];
    }
}
