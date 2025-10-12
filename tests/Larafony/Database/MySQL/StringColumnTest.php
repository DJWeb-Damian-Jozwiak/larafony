<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database\MySQL;

use Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions\StringColumn;
use Larafony\Framework\Tests\TestCase;

class StringColumnTest extends TestCase
{
    public function testCreatesStringColumn(): void
    {
        $column = new StringColumn('name', length: 255, type: 'VARCHAR');

        $this->assertEquals('name', $column->name);
        $this->assertEquals('VARCHAR', $column->type);
    }

    public function testGeneratesSqlDefinition(): void
    {
        $column = new StringColumn('email', length: 255, type: 'VARCHAR');
        $sql = $column->getSqlDefinition();

        $this->assertStringContainsString('email', $sql);
        $this->assertStringContainsString('VARCHAR', $sql);
    }

    public function testSupportsCustomLength(): void
    {
        $column = new StringColumn('code', length: 10, type: 'VARCHAR');
        $sql = $column->getSqlDefinition();

        $this->assertStringContainsString('VARCHAR(10)', $sql);
    }

    public function testSupportsCharType(): void
    {
        $column = new StringColumn('code', length: 5, type: 'CHAR');
        $sql = $column->getSqlDefinition();

        $this->assertStringContainsString('CHAR(5)', $sql);
    }

    public function testSupportsNullable(): void
    {
        $column = new StringColumn('optional', length: 255, type: 'VARCHAR');
        $column->nullable(true);
        $sql = $column->getSqlDefinition();

        $this->assertStringContainsString('NULL', $sql);
    }

    public function testSupportsDefault(): void
    {
        $column = new StringColumn('status', length: 50, type: 'VARCHAR');
        $column = $column->default('active');
        $sql = $column->getSqlDefinition();

        $this->assertStringContainsString('DEFAULT', $sql);
    }
}
