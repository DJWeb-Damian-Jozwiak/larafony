<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database\MySQL;

use Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions\DateTimeColumn;
use Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions\EnumColumn;
use Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions\IntColumn;
use Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions\StringColumn;
use Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnFactory;
use Larafony\Framework\Tests\TestCase;

class ColumnFactoryTest extends TestCase
{
    private ColumnFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new ColumnFactory();
    }

    public function testCreatesIntColumn(): void
    {
        $description = [
            'Field' => 'id',
            'Type' => 'int',
            'Null' => 'NO',
            'Default' => null,
            'Extra' => 'auto_increment',
        ];

        $column = $this->factory->create($description);

        $this->assertInstanceOf(IntColumn::class, $column);
        $this->assertEquals('id', $column->name);
    }

    public function testCreatesBigIntColumn(): void
    {
        $description = [
            'Field' => 'big_id',
            'Type' => 'bigint',
            'Null' => 'NO',
            'Default' => null,
            'Extra' => '',
        ];

        $column = $this->factory->create($description);

        $this->assertInstanceOf(IntColumn::class, $column);
    }

    public function testCreatesSmallIntColumn(): void
    {
        $description = [
            'Field' => 'count',
            'Type' => 'smallint',
            'Null' => 'NO',
            'Default' => null,
            'Extra' => '',
        ];

        $column = $this->factory->create($description);

        $this->assertInstanceOf(IntColumn::class, $column);
    }

    public function testCreatesTinyIntColumn(): void
    {
        $description = [
            'Field' => 'flag',
            'Type' => 'tinyint',
            'Null' => 'NO',
            'Default' => null,
            'Extra' => '',
        ];

        $column = $this->factory->create($description);

        $this->assertInstanceOf(IntColumn::class, $column);
    }

    public function testCreatesMediumIntColumn(): void
    {
        $description = [
            'Field' => 'medium_id',
            'Type' => 'mediumint',
            'Null' => 'NO',
            'Default' => null,
            'Extra' => '',
        ];

        $column = $this->factory->create($description);

        $this->assertInstanceOf(IntColumn::class, $column);
    }

    public function testCreatesVarcharColumn(): void
    {
        $description = [
            'Field' => 'name',
            'Type' => 'varchar',
            'Null' => 'YES',
            'Default' => null,
        ];

        $column = $this->factory->create($description);

        $this->assertInstanceOf(StringColumn::class, $column);
        $this->assertEquals('name', $column->name);
    }

    public function testCreatesCharColumn(): void
    {
        $description = [
            'Field' => 'code',
            'Type' => 'char',
            'Null' => 'NO',
            'Default' => null,
        ];

        $column = $this->factory->create($description);

        $this->assertInstanceOf(StringColumn::class, $column);
    }

    public function testCreatesTextColumn(): void
    {
        $description = [
            'Field' => 'description',
            'Type' => 'text',
            'Null' => 'YES',
            'Default' => null,
        ];

        $column = $this->factory->create($description);

        $this->assertInstanceOf(StringColumn::class, $column);
    }

    public function testCreatesMediumTextColumn(): void
    {
        $description = [
            'Field' => 'content',
            'Type' => 'mediumtext',
            'Null' => 'YES',
            'Default' => null,
        ];

        $column = $this->factory->create($description);

        $this->assertInstanceOf(StringColumn::class, $column);
    }

    public function testCreatesLongTextColumn(): void
    {
        $description = [
            'Field' => 'document',
            'Type' => 'longtext',
            'Null' => 'YES',
            'Default' => null,
        ];

        $column = $this->factory->create($description);

        $this->assertInstanceOf(StringColumn::class, $column);
    }

    public function testCreatesDateTimeColumn(): void
    {
        $description = [
            'Field' => 'created_at',
            'Type' => 'datetime',
            'Null' => 'YES',
            'Default' => null,
            'Extra' => '',
        ];

        $column = $this->factory->create($description);

        $this->assertInstanceOf(DateTimeColumn::class, $column);
        $this->assertEquals('created_at', $column->name);
    }

    public function testCreatesTimestampColumn(): void
    {
        $description = [
            'Field' => 'updated_at',
            'Type' => 'timestamp',
            'Null' => 'NO',
            'Default' => 'CURRENT_TIMESTAMP',
            'Extra' => 'on update CURRENT_TIMESTAMP',
        ];

        $column = $this->factory->create($description);

        $this->assertInstanceOf(DateTimeColumn::class, $column);
    }

    public function testCreatesDateColumn(): void
    {
        $description = [
            'Field' => 'birth_date',
            'Type' => 'date',
            'Null' => 'YES',
            'Default' => null,
            'Extra' => '',
        ];

        $column = $this->factory->create($description);

        $this->assertInstanceOf(DateTimeColumn::class, $column);
    }

    public function testCreatesTimeColumn(): void
    {
        $description = [
            'Field' => 'start_time',
            'Type' => 'time',
            'Null' => 'YES',
            'Default' => null,
            'Extra' => '',
        ];

        $column = $this->factory->create($description);

        $this->assertInstanceOf(DateTimeColumn::class, $column);
    }

    public function testCreatesEnumColumn(): void
    {
        $description = [
            'Field' => 'status',
            'Type' => "enum('active','inactive','pending')",
            'Null' => 'NO',
            'Default' => 'active',
        ];

        $column = $this->factory->create($description);

        $this->assertInstanceOf(EnumColumn::class, $column);
        $this->assertEquals('status', $column->name);
    }

    public function testThrowsExceptionForInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid column type unknown_type');

        $description = [
            'Field' => 'invalid',
            'Type' => 'unknown_type',
            'Null' => 'NO',
            'Default' => null,
        ];

        $this->factory->create($description);
    }
}
