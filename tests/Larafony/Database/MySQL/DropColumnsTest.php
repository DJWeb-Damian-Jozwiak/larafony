<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database\MySQL;

use Larafony\Framework\Database\Drivers\MySQL\Schema\Builders\DropColumns;
use Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions\IntColumn;
use Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions\StringColumn;
use Larafony\Framework\Database\Drivers\MySQL\Schema\TableDefinition;
use Larafony\Framework\Tests\TestCase;

class DropColumnsTest extends TestCase
{
    private DropColumns $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new DropColumns();
    }

    public function testBuildsDropColumnSql(): void
    {
        $emailColumn = new StringColumn('email', length: 255, type: 'VARCHAR');
        $emailColumn->delete(); // Mark as deleted

        $table = new TableDefinition('users', [
            'email' => $emailColumn,
        ]);

        $sql = $this->builder->build($table);

        $this->assertStringContainsString('ALTER TABLE users', $sql);
        $this->assertStringContainsString('DROP COLUMN', $sql);
        $this->assertStringContainsString('email', $sql);
    }

    public function testBuildsDropMultipleColumnsSql(): void
    {
        $emailColumn = new StringColumn('email', length: 255, type: 'VARCHAR');
        $emailColumn->delete();

        $ageColumn = new IntColumn('age', type: 'INT');
        $ageColumn->delete();

        $table = new TableDefinition('users', [
            'email' => $emailColumn,
            'age' => $ageColumn,
        ]);

        $sql = $this->builder->build($table);

        $this->assertStringContainsString('ALTER TABLE users', $sql);
        $this->assertStringContainsString('DROP COLUMN email', $sql);
        $this->assertStringContainsString('DROP COLUMN age', $sql);
    }

    public function testExcludesNonDeletedColumns(): void
    {
        $emailColumn = new StringColumn('email', length: 255, type: 'VARCHAR');
        // Not marked as deleted

        $nameColumn = new StringColumn('name', length: 255, type: 'VARCHAR');
        $nameColumn->delete(); // Marked as deleted

        $table = new TableDefinition('users', [
            'email' => $emailColumn,
            'name' => $nameColumn,
        ]);

        $sql = $this->builder->build($table);

        $this->assertStringContainsString('DROP COLUMN name', $sql);
        $this->assertStringNotContainsString('email', $sql);
    }

    public function testExcludesModifiedColumns(): void
    {
        $emailColumn = new StringColumn('email', length: 255, type: 'VARCHAR');
        $emailColumn->change(); // Mark as modified

        $nameColumn = new StringColumn('name', length: 255, type: 'VARCHAR');
        $nameColumn->delete(); // Marked as deleted

        $table = new TableDefinition('users', [
            'email' => $emailColumn,
            'name' => $nameColumn,
        ]);

        $sql = $this->builder->build($table);

        $this->assertStringContainsString('DROP COLUMN name', $sql);
        $this->assertStringNotContainsString('email', $sql);
    }

    public function testReturnsEmptyStringWhenNoColumnsToDelete(): void
    {
        $emailColumn = new StringColumn('email', length: 255, type: 'VARCHAR');
        // Not marked as deleted

        $table = new TableDefinition('users', [
            'email' => $emailColumn,
        ]);

        $sql = $this->builder->build($table);

        $this->assertEmpty($sql);
    }

    public function testReturnsEmptyStringForEmptyTable(): void
    {
        $table = new TableDefinition('users', []);

        $sql = $this->builder->build($table);

        $this->assertEmpty($sql);
    }
}
