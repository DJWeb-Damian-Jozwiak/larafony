<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database\MySQL;

use Larafony\Framework\Database\Drivers\MySQL\Schema\Builders\ChangeColumns;
use Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions\IntColumn;
use Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions\StringColumn;
use Larafony\Framework\Database\Drivers\MySQL\Schema\TableDefinition;
use Larafony\Framework\Tests\TestCase;

class ChangeColumnsTest extends TestCase
{
    private ChangeColumns $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new ChangeColumns();
    }

    public function testBuildsChangeColumnSql(): void
    {
        $emailColumn = new StringColumn('email', length: 100, type: 'VARCHAR');
        $emailColumn->change(); // Mark as modified

        $table = new TableDefinition('users', [
            'email' => $emailColumn,
        ]);

        $sql = $this->builder->build($table);

        $this->assertStringContainsString('ALTER TABLE users', $sql);
        $this->assertStringContainsString('MODIFY COLUMN', $sql);
        $this->assertStringContainsString('email', $sql);
    }

    public function testBuildsMultipleChangeColumnsSql(): void
    {
        $emailColumn = new StringColumn('email', length: 100, type: 'VARCHAR');
        $emailColumn->change();

        $ageColumn = new IntColumn('age', type: 'INT');
        $ageColumn->change();

        $table = new TableDefinition('users', [
            'email' => $emailColumn,
            'age' => $ageColumn,
        ]);

        $sql = $this->builder->build($table);

        $this->assertStringContainsString('ALTER TABLE users', $sql);
        $this->assertStringContainsString('MODIFY COLUMN', $sql);
        $this->assertStringContainsString('email', $sql);
        $this->assertStringContainsString('age', $sql);
    }

    public function testExcludesUnmodifiedColumns(): void
    {
        $emailColumn = new StringColumn('email', length: 255, type: 'VARCHAR');
        // Not marked as modified

        $nameColumn = new StringColumn('name', length: 100, type: 'VARCHAR');
        $nameColumn->change(); // Marked as modified

        $table = new TableDefinition('users', [
            'email' => $emailColumn,
            'name' => $nameColumn,
        ]);

        $sql = $this->builder->build($table);

        $this->assertStringContainsString('name', $sql);
        $this->assertStringNotContainsString('email', $sql);
    }

    public function testExcludesDeletedColumns(): void
    {
        $emailColumn = new StringColumn('email', length: 255, type: 'VARCHAR');
        $emailColumn->delete(); // Mark as deleted

        $nameColumn = new StringColumn('name', length: 100, type: 'VARCHAR');
        $nameColumn->change(); // Marked as modified

        $table = new TableDefinition('users', [
            'email' => $emailColumn,
            'name' => $nameColumn,
        ]);

        $sql = $this->builder->build($table);

        $this->assertStringContainsString('name', $sql);
        $this->assertStringNotContainsString('email', $sql);
    }

    public function testReturnsEmptyStringWhenNoColumnsToChange(): void
    {
        $emailColumn = new StringColumn('email', length: 255, type: 'VARCHAR');
        // Not marked as modified

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
