<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL;

use Larafony\Framework\Database\Base\Contracts\ConnectionContract;
use PDO;
use SensitiveParameter;

final class Connection implements ConnectionContract
{
    private ?PDO $connection = null;

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
        if ($this->connection) {
            return;
        }

        $this->connection = $this->connectMysql(
            $this->host,
            $this->port,
            $this->database,
            $this->username,
            $this->password,
            $this->charset
        );
    }

    public function disconnect(): void
    {
        $this->connection = null;
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        if ($this->connection === null) {
            throw new \RuntimeException('Not connected to database. Call connect() first.');
        }

        $statement = $this->connection->prepare($sql);
        $statement->execute(array_values($params));

        // Close cursor to prevent "Cannot execute queries while there are pending result sets"
        // This is needed when ATTR_EMULATE_PREPARES is true and we use multi-query
        $statement->closeCursor();

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

    public function getLastInsertId(): ?string
    {
        $id = $this->connection?->lastInsertId();
        return $id === false ? null : $id;
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
}
