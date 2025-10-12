<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database\MySQL;

use Larafony\Framework\Database\Drivers\MySQL\Schema\Builders\CreateTableBuilder;
use Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions\IntColumn;
use Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions\StringColumn;
use Larafony\Framework\Database\Drivers\MySQL\Schema\IndexDefinitions\PrimaryIndex;
use Larafony\Framework\Database\Drivers\MySQL\Schema\TableDefinition;
use Larafony\Framework\Tests\TestCase;

class CreateTableBuilderTest extends TestCase
{
    private CreateTableBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new CreateTableBuilder();
    }

    public function testBuildsSimpleCreateTableSql(): void
    {
        $idColumn = new IntColumn('id', type: 'INT');
        $nameColumn = new StringColumn('name', length: 255, type: 'VARCHAR');

        $table = new TableDefinition('users', [
            'id' => $idColumn,
            'name' => $nameColumn,
        ]);

        $sql = $this->builder->build($table);

        $this->assertStringContainsString('CREATE TABLE users', $sql);
        $this->assertStringContainsString('id', $sql);
        $this->assertStringContainsString('name', $sql);
    }

    public function testBuildsTableWithPrimaryKey(): void
    {
        $idColumn = new IntColumn('id', type: 'INT');
        $table = new TableDefinition('users', ['id' => $idColumn]);

        // Manually add index using reflection since addIndex is protected
        $reflection = new \ReflectionClass($table);
        $property = $reflection->getProperty('indexes');
        $property->setValue($table, ['primary' => new PrimaryIndex('users', 'id')]);

        $sql = $this->builder->build($table);

        $this->assertStringContainsString('CREATE TABLE users', $sql);
        $this->assertStringContainsString('PRIMARY KEY', $sql);
    }

    public function testBuildsTableWithMultipleColumns(): void
    {
        $table = new TableDefinition('posts', [
            'id' => new IntColumn('id', type: 'INT'),
            'title' => new StringColumn('title', length: 255, type: 'VARCHAR'),
            'content' => new StringColumn('content', length: 65535, type: 'TEXT'),
        ]);

        $sql = $this->builder->build($table);

        $this->assertStringContainsString('CREATE TABLE posts', $sql);
        $this->assertStringContainsString('id', $sql);
        $this->assertStringContainsString('title', $sql);
        $this->assertStringContainsString('content', $sql);
    }

    public function testBuildsEmptyTableWithNoIndexes(): void
    {
        $table = new TableDefinition('empty', [
            'id' => new IntColumn('id', type: 'INT'),
        ]);

        $sql = $this->builder->build($table);

        $this->assertStringContainsString('CREATE TABLE empty', $sql);
        $this->assertStringContainsString('id', $sql);
    }
}
