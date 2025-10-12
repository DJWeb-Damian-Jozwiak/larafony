<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database\MySQL;

use Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions\IntColumn;
use Larafony\Framework\Tests\TestCase;

class IntColumnTest extends TestCase
{
    public function testCreatesIntColumn(): void
    {
        $column = new IntColumn('id', type: 'INT');

        $this->assertEquals('id', $column->name);
        $this->assertEquals('INT', $column->type);
    }

    public function testGeneratesSqlDefinition(): void
    {
        $column = new IntColumn('age', type: 'INT');
        $sql = $column->getSqlDefinition();

        $this->assertStringContainsString('age', $sql);
        $this->assertStringContainsString('INT', $sql);
    }

    public function testSupportsLength(): void
    {
        $column = new IntColumn('count', type: 'INT');
        $column = $column->length(10);
        $sql = $column->getSqlDefinition();

        $this->assertStringContainsString('INT(10)', $sql);
    }

    public function testSupportsUnsigned(): void
    {
        $column = new IntColumn('count', type: 'INT');
        $column = $column->unsigned(true);
        $sql = $column->getSqlDefinition();

        $this->assertStringContainsString('UNSIGNED', $sql);
    }

    public function testSupportsAutoIncrement(): void
    {
        $column = new IntColumn('id', type: 'INT');
        $column = $column->autoIncrement(true);
        $sql = $column->getSqlDefinition();

        $this->assertStringContainsString('AUTO_INCREMENT', $sql);
    }

    public function testSupportsNullable(): void
    {
        $column = new IntColumn('optional', type: 'INT');
        $column = $column->nullable(true);
        $sql = $column->getSqlDefinition();

        $this->assertStringContainsString('NULL', $sql);
    }

    public function testSupportsNotNullable(): void
    {
        $column = new IntColumn('required', type: 'INT');
        $column = $column->nullable(false);
        $sql = $column->getSqlDefinition();

        $this->assertStringContainsString('NOT NULL', $sql);
    }

    public function testSupportsDefault(): void
    {
        $column = new IntColumn('status', type: 'INT');
        $column = $column->default(0);
        $sql = $column->getSqlDefinition();

        $this->assertStringContainsString('DEFAULT', $sql);
        $this->assertStringContainsString('0', $sql);
    }
}
