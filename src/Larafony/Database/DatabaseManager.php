<?php

declare(strict_types=1);

namespace Larafony\Framework\Database;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Database\Base\Contracts\ConnectionContract;
use Larafony\Framework\Database\Base\Query\QueryBuilder as BaseQueryBuilder;
use Larafony\Framework\Database\Base\Schema\SchemaBuilder;
use Larafony\Framework\Database\Drivers\MySQL\Connection;
use Larafony\Framework\Database\Drivers\MySQL\QueryBuilder as MySQLQueryBuilder;
use Larafony\Framework\Database\Drivers\MySQL\QueryEventDispatcher;
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
    public function __construct(
        protected array $config = [],
        protected ?ContainerContract $container = null,
    ) {
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

    /**
     * Begin a fluent query against a database table.
     * Creates a NEW QueryBuilder instance each time (no caching).
     */
    public function table(string $table, ?string $connectionName = null): BaseQueryBuilder
    {
        $connectionName = $connectionName ?? $this->defaultConnection;
        $connection = $this->connection($connectionName);

        $config = $this->getConfig($connectionName);
        $driver = $config['driver'] ?? 'mysql';

        $queryBuilder = match ($driver) {
            'mysql' => new MySQLQueryBuilder($connection),
            default => throw new \InvalidArgumentException("Unsupported driver: {$driver}"),
        };

        return $queryBuilder->table($table);
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
    protected function createMySQLConnection(array $config): ConnectionContract
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

        if ($this->container !== null) {
            $projectRoot = $this->container->getBinding('base_path') . '/';
            $dispatcher = new QueryEventDispatcher($this->container, $projectRoot);
            $connection->withQueryEventDispatcher($dispatcher);
        }

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
