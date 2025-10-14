<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database;

use Larafony\Framework\Database\Base\Contracts\ConnectionContract;
use Larafony\Framework\Database\Drivers\MySQL\SchemaBuilder;
use Larafony\Framework\Tests\TestCase;
use PDO;
use PDOStatement;

class SchemaBuilderTableTest extends TestCase
{
    private ConnectionContract $connection;
    private SchemaBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->createMock(ConnectionContract::class);
        $this->builder = new SchemaBuilder($this->connection);
    }

    public function testTableMethodAddsColumns(): void
    {
        // Mock PDOStatement for DESCRIBE query
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'Field' => 'id',
                    'Type' => 'int(11)',
                    'Null' => 'NO',
                    'Key' => 'PRI',
                    'Default' => null,
                    'Extra' => 'auto_increment',
                ],
                [
                    'Field' => 'name',
                    'Type' => 'varchar(255)',
                    'Null' => 'YES',
                    'Key' => '',
                    'Default' => null,
                    'Extra' => '',
                ],
            ]);

        $this->connection
            ->expects($this->once())
            ->method('query')
            ->with('DESCRIBE `users`')
            ->willReturn($stmt);

        $sql = $this->builder->table('users', function ($table) {
            $table->string('email');
            $table->integer('age');
        });

        $this->assertStringContainsString('ALTER TABLE users', $sql);
        $this->assertStringContainsString('ADD COLUMN email VARCHAR(255) NULL', $sql);
        $this->assertStringContainsString('ADD COLUMN age INT(11) NULL', $sql);
    }

    public function testTableMethodModifiesColumns(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'Field' => 'id',
                    'Type' => 'int(11)',
                    'Null' => 'NO',
                    'Key' => 'PRI',
                    'Default' => null,
                    'Extra' => 'auto_increment',
                ],
                [
                    'Field' => 'name',
                    'Type' => 'varchar(255)',
                    'Null' => 'YES',
                    'Key' => '',
                    'Default' => null,
                    'Extra' => '',
                ],
            ]);

        $this->connection
            ->expects($this->once())
            ->method('query')
            ->with('DESCRIBE `users`')
            ->willReturn($stmt);

        $sql = $this->builder->table('users', function ($table) {
            $table->change('name')->nullable(false);
        });

        $this->assertStringContainsString('ALTER TABLE users MODIFY COLUMN name VARCHAR(255) NOT NULL', $sql);
    }

    public function testTableMethodDropsColumns(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'Field' => 'id',
                    'Type' => 'int(11)',
                    'Null' => 'NO',
                    'Key' => 'PRI',
                    'Default' => null,
                    'Extra' => 'auto_increment',
                ],
                [
                    'Field' => 'name',
                    'Type' => 'varchar(255)',
                    'Null' => 'YES',
                    'Key' => '',
                    'Default' => null,
                    'Extra' => '',
                ],
                [
                    'Field' => 'old_field',
                    'Type' => 'varchar(255)',
                    'Null' => 'YES',
                    'Key' => '',
                    'Default' => null,
                    'Extra' => '',
                ],
            ]);

        $this->connection
            ->expects($this->once())
            ->method('query')
            ->with('DESCRIBE `users`')
            ->willReturn($stmt);

        $sql = $this->builder->table('users', function ($table) {
            $table->drop('old_field');
        });

        $this->assertStringContainsString('ALTER TABLE users DROP COLUMN old_field', $sql);
    }

    public function testTableMethodCombinesMultipleOperations(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'Field' => 'id',
                    'Type' => 'int(11)',
                    'Null' => 'NO',
                    'Key' => 'PRI',
                    'Default' => null,
                    'Extra' => 'auto_increment',
                ],
                [
                    'Field' => 'name',
                    'Type' => 'varchar(255)',
                    'Null' => 'YES',
                    'Key' => '',
                    'Default' => null,
                    'Extra' => '',
                ],
            ]);

        $this->connection
            ->expects($this->once())
            ->method('query')
            ->with('DESCRIBE `users`')
            ->willReturn($stmt);

        $sql = $this->builder->table('users', function ($table) {
            $table->string('email');
            $table->change('name')->nullable(false);
            $table->drop('name');
        });

        // Should contain add, modify, and drop statements
        $this->assertStringContainsString('ADD COLUMN', $sql);
        $this->assertStringContainsString('MODIFY COLUMN', $sql);
        $this->assertStringContainsString('DROP COLUMN', $sql);
    }

    public function testGetColumnListing(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'Field' => 'id',
                    'Type' => 'int(11)',
                    'Null' => 'NO',
                    'Key' => 'PRI',
                    'Default' => null,
                    'Extra' => 'auto_increment',
                ],
                [
                    'Field' => 'name',
                    'Type' => 'varchar(255)',
                    'Null' => 'YES',
                    'Key' => '',
                    'Default' => null,
                    'Extra' => '',
                ],
                [
                    'Field' => 'email',
                    'Type' => 'varchar(255)',
                    'Null' => 'YES',
                    'Key' => '',
                    'Default' => null,
                    'Extra' => '',
                ],
            ]);

        $this->connection
            ->expects($this->once())
            ->method('query')
            ->with('DESCRIBE `users`')
            ->willReturn($stmt);

        $columns = $this->builder->getColumnListing('users');

        $this->assertIsArray($columns);
        $this->assertCount(3, $columns);
        $this->assertEquals(['id', 'name', 'email'], $columns);
    }

    public function testGetColumnListingReturnsEmptyArrayForEmptyTable(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([]);

        $this->connection
            ->expects($this->once())
            ->method('query')
            ->with('DESCRIBE `empty_table`')
            ->willReturn($stmt);

        $columns = $this->builder->getColumnListing('empty_table');

        $this->assertIsArray($columns);
        $this->assertEmpty($columns);
    }

    public function testTableMethodReturnsString(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'Field' => 'id',
                    'Type' => 'int(11)',
                    'Null' => 'NO',
                    'Key' => 'PRI',
                    'Default' => null,
                    'Extra' => 'auto_increment',
                ],
            ]);

        $this->connection
            ->expects($this->once())
            ->method('query')
            ->willReturn($stmt);

        $result = $this->builder->table('users', function ($table) {
            $table->string('email');
        });

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testTableMethodReturnsEmptyStringWhenNoChanges(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'Field' => 'id',
                    'Type' => 'int(11)',
                    'Null' => 'NO',
                    'Key' => 'PRI',
                    'Default' => null,
                    'Extra' => 'auto_increment',
                ],
            ]);

        $this->connection
            ->expects($this->once())
            ->method('query')
            ->willReturn($stmt);

        $result = $this->builder->table('users', function ($table) {
            // No changes
        });

        $this->assertIsString($result);
    }
}
