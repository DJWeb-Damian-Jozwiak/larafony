<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database\MySQL;

use Larafony\Framework\Database\Base\Contracts\ConnectionContract;
use Larafony\Framework\Database\Drivers\MySQL\Schema\DatabaseInfo;
use Larafony\Framework\Database\Drivers\MySQL\Schema\TableDefinition;
use Larafony\Framework\Tests\TestCase;
use PDO;

class DatabaseInfoTest extends TestCase
{
    public function testGetTablesReturnsArrayOfTableNames(): void
    {
        $mockStatement = $this->createMock(\PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_COLUMN)
            ->willReturn(['users', 'posts', 'comments']);

        $mockConnection = $this->createMock(ConnectionContract::class);
        $mockConnection->expects($this->once())
            ->method('query')
            ->with('SHOW TABLES')
            ->willReturn($mockStatement);

        $databaseInfo = new DatabaseInfo($mockConnection);
        $tables = $databaseInfo->getTables();

        $this->assertIsArray($tables);
        $this->assertCount(3, $tables);
        $this->assertEquals(['users', 'posts', 'comments'], $tables);
    }

    public function testGetTablesReturnsEmptyArrayWhenNoTables(): void
    {
        $mockStatement = $this->createMock(\PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_COLUMN)
            ->willReturn([]);

        $mockConnection = $this->createMock(ConnectionContract::class);
        $mockConnection->expects($this->once())
            ->method('query')
            ->with('SHOW TABLES')
            ->willReturn($mockStatement);

        $databaseInfo = new DatabaseInfo($mockConnection);
        $tables = $databaseInfo->getTables();

        $this->assertIsArray($tables);
        $this->assertEmpty($tables);
    }

    public function testGetTableReturnsTableDefinition(): void
    {
        $mockStatement = $this->createMock(\PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'Field' => 'id',
                    'Type' => 'int',
                    'Null' => 'NO',
                    'Default' => null,
                    'Extra' => 'auto_increment',
                ],
                [
                    'Field' => 'name',
                    'Type' => 'varchar',
                    'Null' => 'YES',
                    'Default' => null,
                ],
            ]);

        $mockConnection = $this->createMock(ConnectionContract::class);
        $mockConnection->expects($this->once())
            ->method('query')
            ->with('DESCRIBE `users`')
            ->willReturn($mockStatement);

        $databaseInfo = new DatabaseInfo($mockConnection);
        $table = $databaseInfo->getTable('users');

        $this->assertInstanceOf(TableDefinition::class, $table);
        $this->assertEquals('users', $table->tableName);
        $this->assertCount(2, $table->columns);
    }

    public function testGetTableWithSingleColumn(): void
    {
        $mockStatement = $this->createMock(\PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'Field' => 'id',
                    'Type' => 'bigint',
                    'Null' => 'NO',
                    'Default' => null,
                    'Extra' => '',
                ],
            ]);

        $mockConnection = $this->createMock(ConnectionContract::class);
        $mockConnection->expects($this->once())
            ->method('query')
            ->with('DESCRIBE `posts`')
            ->willReturn($mockStatement);

        $databaseInfo = new DatabaseInfo($mockConnection);
        $table = $databaseInfo->getTable('posts');

        $this->assertInstanceOf(TableDefinition::class, $table);
        $this->assertEquals('posts', $table->tableName);
        $this->assertCount(1, $table->columns);
    }

    public function testGetTableUsesColumnFactory(): void
    {
        $mockStatement = $this->createMock(\PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'Field' => 'created_at',
                    'Type' => 'datetime',
                    'Null' => 'YES',
                    'Default' => null,
                    'Extra' => '',
                ],
            ]);

        $mockConnection = $this->createMock(ConnectionContract::class);
        $mockConnection->expects($this->once())
            ->method('query')
            ->willReturn($mockStatement);

        $databaseInfo = new DatabaseInfo($mockConnection);
        $table = $databaseInfo->getTable('logs');

        $this->assertCount(1, $table->columns);
    }
}
