<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database\MySQL;

use Larafony\Framework\Database\Drivers\MySQL\Schema\Builders\AddColumns;
use Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions\IntColumn;
use Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions\StringColumn;
use Larafony\Framework\Database\Drivers\MySQL\Schema\TableDefinition;
use Larafony\Framework\Tests\TestCase;

class AddColumnsTest extends TestCase
{
    private AddColumns $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new AddColumns();
    }

    public function testBuildsAddColumnSql(): void
    {
        $emailColumn = new StringColumn('email', length: 255, type: 'VARCHAR');

        $table = new TableDefinition('users', [
            'email' => $emailColumn,
        ]);

        $sql = $this->builder->build($table);

        $this->assertStringContainsString('ALTER TABLE users', $sql);
        $this->assertStringContainsString('ADD COLUMN', $sql);
        $this->assertStringContainsString('email', $sql);
    }

    public function testBuildsAddMultipleColumnsSql(): void
    {
        $table = new TableDefinition('users', [
            'email' => new StringColumn('email', length: 255, type: 'VARCHAR'),
            'age' => new IntColumn('age', type: 'INT'),
        ]);

        $sql = $this->builder->build($table);

        $this->assertStringContainsString('ALTER TABLE users', $sql);
        $this->assertStringContainsString('ADD COLUMN', $sql);
        $this->assertStringContainsString('email', $sql);
        $this->assertStringContainsString('age', $sql);
    }

    public function testExcludesModifiedColumns(): void
    {
        $emailColumn = new StringColumn('email', length: 255, type: 'VARCHAR');
        $emailColumn->change(); // Mark as modified

        $nameColumn = new StringColumn('name', length: 255, type: 'VARCHAR');

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

        $nameColumn = new StringColumn('name', length: 255, type: 'VARCHAR');

        $table = new TableDefinition('users', [
            'email' => $emailColumn,
            'name' => $nameColumn,
        ]);

        $sql = $this->builder->build($table);

        $this->assertStringContainsString('name', $sql);
        $this->assertStringNotContainsString('email', $sql);
    }

    public function testReturnsEmptyStringWhenNoColumnsToAdd(): void
    {
        $emailColumn = new StringColumn('email', length: 255, type: 'VARCHAR');
        $emailColumn->change(); // Mark as modified

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
