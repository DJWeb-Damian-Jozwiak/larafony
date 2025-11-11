<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL;

use Larafony\Framework\Web\Application;
use Larafony\Framework\Database\Base\Contracts\ConnectionContract;
use Larafony\Framework\Events\Database\QueryExecuted;
use Larafony\Framework\View\BladeSourceMapper;
use PDO;
use Psr\EventDispatcher\EventDispatcherInterface;
use SensitiveParameter;

final class Connection implements ConnectionContract
{
    public private(set) PDO $connection;

    public function __construct(
        private readonly ?string $host = null,
        private readonly ?int $port = null,
        private readonly ?string $database = null,
        private readonly ?string $username = null,
        #[SensitiveParameter]
        private readonly ?string $password = null,
        private readonly ?string $charset = 'utf8mb4'
    ) {
    }

    public function connect(): void
    {
        $this->connection = $this->connectMysql(
            $this->host,
            $this->port,
            $this->database,
            $this->username,
            $this->password,
            $this->charset
        );
    }

    /**
     * Execute a SELECT query and return all results
     * Cursor is automatically closed after fetching
     *
     * @param string $sql
     * @param array<int, mixed> $params
     *
     * @return array<int, array<string, mixed>>
     */
    public function select(string $sql, array $params = []): array
    {
        $startTime = microtime(true);
        $statement = $this->connection->prepare($sql);
        $statement->execute(array_values($params));
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $statement->closeCursor();
        $time = (microtime(true) - $startTime) * 1000; // Convert to ms

        $this->dispatchQueryEvent($sql, $params, $time);

        return $result;
    }

    /**
     * Execute an INSERT/UPDATE/DELETE query and return affected rows
     * Cursor is automatically closed after getting row count
     *
     * @param string $sql
     * @param array<int, mixed> $params
     *
     * @return int Number of affected rows
     */
    public function execute(string $sql, array $params = []): int
    {
        $startTime = microtime(true);
        $statement = $this->connection->prepare($sql);
        $statement->execute(array_values($params));
        $count = $statement->rowCount();
        $statement->closeCursor();
        $time = (microtime(true) - $startTime) * 1000; // Convert to ms

        $this->dispatchQueryEvent($sql, $params, $time);

        return $count;
    }

    /**
     * Raw query execution - returns PDOStatement
     * Caller is responsible for cursor management (e.g., multi-statement queries in migrations)
     *
     * @param string $sql
     * @param array<int, mixed> $params
     *
     * @return \PDOStatement
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute(array_values($params));

        return $statement;
    }

    /**
     * @return array<int, int|false>
     */
    public function getConnectionOptions(): array
    {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Set to true to allow multiple statements in one query (for Schema alterations)
            // Still safe because we use prepared statements with bound parameters
            PDO::ATTR_EMULATE_PREPARES => true,
        ];
    }

    public function getLastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    public function quote(int|float|string|bool|null $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        return $this->connection->quote((string) $value);
    }

    public function connectMysql(
        ?string $host,
        ?int $port,
        ?string $database,
        ?string $username,
        #[SensitiveParameter]
        ?string $password,
        ?string $charset
    ): PDO {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $host,
            $port,
            $database,
            $charset
        );
        return new PDO(
            $dsn,
            $username,
            $password,
            $this->getConnectionOptions()
        );
    }

    /**
     * @param array<int, mixed> $params
     */
    private function dispatchQueryEvent(string $sql, array $params, float $time): void
    {
        $app = Application::instance();
        if (! $app->has(EventDispatcherInterface::class)) {
            return;
        }

        $rawSql = $sql;
        foreach ($params as $param) {
            $value = $this->quote($param);
            $rawSql = preg_replace('/\?/', $value, $rawSql, 1);
        }

        // Get backtrace (limit to 10 frames)
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $projectRoot = Application::instance()->base_path . '/';

        $backtrace = array_map(function ($frame) use ($projectRoot) {
            $file = $frame['file'] ?? null;
            $line = $frame['line'] ?? null;
            $compiledFile = null;
            $compiledLine = null;

            // Try to resolve compiled Blade files to original source
            if ($file !== null && $line !== null && str_contains($file, '/cache/blade/')) {
                $compiledFile = $file;
                $compiledLine = $line;

                $resolved = BladeSourceMapper::resolve($file, $line);
                if ($resolved !== null) {
                    $file = $resolved['file'];
                    $line = $resolved['line'];
                }
            }

            // Remove project path prefix
            if ($file !== null && str_starts_with($file, $projectRoot)) {
                $file = substr($file, strlen($projectRoot));
            }

            return [
                'file' => $file,
                'line' => $line,
                'class' => $frame['class'] ?? null,
                'function' => $frame['function'] ?? null,
                'compiled_file' => $compiledFile,
                'compiled_line' => $compiledLine,
            ];
        }, $trace);

        $eventDispatcher = $app->get(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(new QueryExecuted(
            $sql,
            $rawSql,
            $time,
            'default',
            $backtrace
        ));
    }
}
