<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database\MySQL;

use Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions\DateTimeColumn;
use Larafony\Framework\Tests\TestCase;

class DateTimeColumnTest extends TestCase
{
    public function testCreatesDateTimeColumn(): void
    {
        $column = new DateTimeColumn('created_at', type: 'DATETIME');

        $this->assertEquals('created_at', $column->name);
        $this->assertEquals('DATETIME', $column->type);
    }

    public function testGeneratesSqlDefinition(): void
    {
        $column = new DateTimeColumn('updated_at', type: 'DATETIME');
        $sql = $column->getSqlDefinition();

        $this->assertStringContainsString('updated_at', $sql);
        $this->assertStringContainsString('DATETIME', $sql);
    }

    public function testSupportsTimestampType(): void
    {
        $column = new DateTimeColumn('logged_at', type: 'TIMESTAMP');
        $sql = $column->getSqlDefinition();

        $this->assertStringContainsString('TIMESTAMP', $sql);
    }

    public function testSupportsDateType(): void
    {
        $column = new DateTimeColumn('birth_date', type: 'DATE');
        $sql = $column->getSqlDefinition();

        $this->assertStringContainsString('DATE', $sql);
    }

    public function testSupportsTimeType(): void
    {
        $column = new DateTimeColumn('start_time', type: 'TIME');
        $sql = $column->getSqlDefinition();

        $this->assertStringContainsString('TIME', $sql);
    }

    public function testSupportsNullable(): void
    {
        $column = new DateTimeColumn('optional_date', type: 'DATETIME');
        $column->nullable(true);
        $sql = $column->getSqlDefinition();

        $this->assertStringContainsString('NULL', $sql);
    }

    public function testSupportsNotNullable(): void
    {
        $column = new DateTimeColumn('required_date', nullable: false, type: 'DATETIME');
        $sql = $column->getSqlDefinition();

        $this->assertStringContainsString('NOT NULL', $sql);
    }

    public function testSupportsDefault(): void
    {
        $column = new DateTimeColumn('registered_at', type: 'DATETIME');
        $column = $column->current('CURRENT_TIMESTAMP');
        $sql = $column->getSqlDefinition();

        $this->assertStringContainsString('DEFAULT', $sql);
        $this->assertStringContainsString('CURRENT_TIMESTAMP', $sql);
    }

    public function testSupportsOnUpdate(): void
    {
        $column = new DateTimeColumn('modified_at', type: 'TIMESTAMP');
        $column = $column->currentOnUpdate('ON UPDATE CURRENT_TIMESTAMP');
        $sql = $column->getSqlDefinition();

        $this->assertStringContainsString('ON UPDATE CURRENT_TIMESTAMP', $sql);
    }

    public function testCreatesFromArrayDescription(): void
    {
        $description = [
            'Field' => 'created_at',
            'Type' => 'DATETIME',
            'Null' => 'YES',
            'Default' => 'CURRENT_TIMESTAMP',
            'Extra' => '',
        ];

        $column = DateTimeColumn::fromArrayDescription($description);

        $this->assertEquals('created_at', $column->name);
        $this->assertEquals('DATETIME', $column->type);
        $this->assertTrue($column->nullable);
    }
}
