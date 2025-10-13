<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Schema;

use Larafony\Framework\Database\Base\Contracts\ConnectionContract;
use Larafony\Framework\Database\Base\Contracts\DatabaseInfoContract;
use PDO;

class DatabaseInfo implements DatabaseInfoContract
{
    public function __construct(private readonly ConnectionContract $connection)
    {
    }

    /**
     * @return array<int, string>
     */
    public function getTables(): array
    {
        $sql = 'SHOW TABLES';
        $stmt = $this->connection->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function getTable(string $tableName): TableDefinition
    {
        $factory = new ColumnFactory();
        $stmt = $this->connection->query("DESCRIBE `{$tableName}`");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $columns = array_map(static fn (array $row) => $factory->create($row), $data);
        return new TableDefinition($tableName, $columns);
    }
}
