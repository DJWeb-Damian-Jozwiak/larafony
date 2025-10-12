<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database\MySQL;

use Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions\TextColumn;
use Larafony\Framework\Tests\TestCase;

class TextColumnTest extends TestCase
{
    public function testCreatesTextColumn(): void
    {
        $column = new TextColumn('description', type: 'TEXT');

        $this->assertEquals('description', $column->name);
        $this->assertEquals('TEXT', $column->type);
    }

    public function testGeneratesSqlDefinition(): void
    {
        $column = new TextColumn('content', type: 'TEXT');
        $sql = $column->getSqlDefinition();

        $this->assertStringContainsString('content', $sql);
        $this->assertStringContainsString('TEXT', $sql);
    }

    public function testSupportsMediumText(): void
    {
        $column = new TextColumn('article', type: 'MEDIUMTEXT');
        $sql = $column->getSqlDefinition();

        $this->assertStringContainsString('MEDIUMTEXT', $sql);
    }

    public function testSupportsLongText(): void
    {
        $column = new TextColumn('document', type: 'LONGTEXT');
        $sql = $column->getSqlDefinition();

        $this->assertStringContainsString('LONGTEXT', $sql);
    }

    public function testSupportsJson(): void
    {
        $column = new TextColumn('data', type: 'JSON');
        $sql = $column->getSqlDefinition();

        $this->assertStringContainsString('JSON', $sql);
    }

    public function testSupportsNullable(): void
    {
        $column = new TextColumn('optional', type: 'TEXT');
        $column->nullable(true);
        $sql = $column->getSqlDefinition();

        $this->assertStringContainsString('NULL', $sql);
    }

    public function testSupportsNotNullable(): void
    {
        $column = new TextColumn('required', nullable: false, type: 'TEXT');
        $sql = $column->getSqlDefinition();

        $this->assertStringContainsString('NOT NULL', $sql);
    }

    public function testCreatesFromArrayDescription(): void
    {
        $description = [
            'Field' => 'content',
            'Null' => 'YES',
        ];

        $column = TextColumn::fromArrayDescription($description);

        $this->assertEquals('content', $column->name);
        $this->assertTrue($column->nullable);
    }
}
