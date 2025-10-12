<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database\MySQL;

use Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions\EnumColumn;
use Larafony\Framework\Tests\TestCase;

class EnumColumnTest extends TestCase
{
    public function testCreatesEnumColumn(): void
    {
        $column = new EnumColumn('status', ['active', 'inactive', 'pending']);

        $this->assertEquals('status', $column->name);
        $this->assertEquals('ENUM', $column->type);
        $this->assertEquals(['active', 'inactive', 'pending'], $column->values);
    }

    public function testGeneratesSqlDefinition(): void
    {
        $column = new EnumColumn('role', ['admin', 'user', 'guest']);
        $sql = $column->getSqlDefinition();

        $this->assertStringContainsString('role', $sql);
        $this->assertStringContainsString('ENUM', $sql);
        $this->assertStringContainsString("'admin'", $sql);
        $this->assertStringContainsString("'user'", $sql);
        $this->assertStringContainsString("'guest'", $sql);
    }

    public function testSupportsNullable(): void
    {
        $column = new EnumColumn('optional_status', ['yes', 'no']);
        $column->nullable(true);
        $sql = $column->getSqlDefinition();

        $this->assertStringContainsString('NULL', $sql);
    }

    public function testSupportsNotNullable(): void
    {
        $column = new EnumColumn('required_status', ['active', 'inactive'], nullable: false);
        $sql = $column->getSqlDefinition();

        $this->assertStringContainsString('NOT NULL', $sql);
    }

    public function testSupportsDefault(): void
    {
        $column = new EnumColumn('visibility', ['public', 'private', 'draft'], default: 'draft');
        $sql = $column->getSqlDefinition();

        $this->assertStringContainsString('DEFAULT', $sql);
        $this->assertStringContainsString("'draft'", $sql);
    }

    public function testCreatesFromArrayDescription(): void
    {
        $description = [
            'Field' => 'status',
            'Type' => "enum('active','inactive','pending')",
            'Null' => 'YES',
            'Default' => 'pending',
        ];

        $column = EnumColumn::fromArrayDescription($description);

        $this->assertEquals('status', $column->name);
        $this->assertEquals(['active', 'inactive', 'pending'], $column->values);
        $this->assertTrue($column->nullable);
    }
}
