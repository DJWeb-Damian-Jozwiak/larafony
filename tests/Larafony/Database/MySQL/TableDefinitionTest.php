<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database\MySQL;

use Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions\DateTimeColumn;
use Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions\IntColumn;
use Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions\StringColumn;
use Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions\TextColumn;
use Larafony\Framework\Database\Drivers\MySQL\Schema\IndexDefinitions\NormalIndex;
use Larafony\Framework\Database\Drivers\MySQL\Schema\IndexDefinitions\PrimaryIndex;
use Larafony\Framework\Database\Drivers\MySQL\Schema\IndexDefinitions\UniqueIndex;
use Larafony\Framework\Database\Drivers\MySQL\Schema\TableDefinition;
use Larafony\Framework\Tests\TestCase;

class TableDefinitionTest extends TestCase
{
    public function testCreatesTableDefinition(): void
    {
        $table = new TableDefinition('users');

        $this->assertEquals('users', $table->tableName);
        $this->assertEmpty($table->columns);
    }

    public function testIntegerReturnsIntColumn(): void
    {
        $table = new TableDefinition('users');

        $column = $table->integer('age');

        $this->assertInstanceOf(IntColumn::class, $column);
        $this->assertEquals('age', $column->name);
        $this->assertEquals('INT', $column->type);
    }

    public function testBigIntegerReturnsIntColumnWithLength(): void
    {
        $table = new TableDefinition('users');

        $column = $table->bigInteger('id');

        $this->assertInstanceOf(IntColumn::class, $column);
        $this->assertEquals('id', $column->name);
    }

    public function testSmallIntegerReturnsIntColumnWithLength(): void
    {
        $table = new TableDefinition('users');

        $column = $table->smallInteger('count');

        $this->assertInstanceOf(IntColumn::class, $column);
        $this->assertEquals('count', $column->name);
    }

    public function testStringReturnsStringColumn(): void
    {
        $table = new TableDefinition('users');

        $column = $table->string('name', 100);

        $this->assertInstanceOf(StringColumn::class, $column);
        $this->assertEquals('name', $column->name);
        $this->assertEquals('VARCHAR', $column->type);
    }

    public function testCharReturnsStringColumnWithCharType(): void
    {
        $table = new TableDefinition('users');

        $column = $table->char('code', 5);

        $this->assertInstanceOf(StringColumn::class, $column);
        $this->assertEquals('code', $column->name);
        $this->assertEquals('CHAR', $column->type);
    }

    public function testTextReturnsTextColumn(): void
    {
        $table = new TableDefinition('posts');

        $column = $table->text('content');

        $this->assertInstanceOf(TextColumn::class, $column);
        $this->assertEquals('content', $column->name);
        $this->assertEquals('TEXT', $column->type);
    }

    public function testMediumTextReturnsTextColumn(): void
    {
        $table = new TableDefinition('posts');

        $column = $table->mediumText('description');

        $this->assertInstanceOf(TextColumn::class, $column);
        $this->assertEquals('description', $column->name);
        $this->assertEquals('MEDIUMTEXT', $column->type);
    }

    public function testLongTextReturnsTextColumn(): void
    {
        $table = new TableDefinition('posts');

        $column = $table->longText('document');

        $this->assertInstanceOf(TextColumn::class, $column);
        $this->assertEquals('document', $column->name);
        $this->assertEquals('LONGTEXT', $column->type);
    }

    public function testJsonReturnsTextColumn(): void
    {
        $table = new TableDefinition('settings');

        $column = $table->json('data');

        $this->assertInstanceOf(TextColumn::class, $column);
        $this->assertEquals('data', $column->name);
        $this->assertEquals('JSON', $column->type);
    }

    public function testDateTimeReturnsDateTimeColumn(): void
    {
        $table = new TableDefinition('logs');

        $column = $table->dateTime('created_at');

        $this->assertInstanceOf(DateTimeColumn::class, $column);
        $this->assertEquals('created_at', $column->name);
        $this->assertEquals('DATETIME', $column->type);
    }

    public function testTimestampReturnsDateTimeColumn(): void
    {
        $table = new TableDefinition('logs');

        $column = $table->timestamp('updated_at');

        $this->assertInstanceOf(DateTimeColumn::class, $column);
        $this->assertEquals('updated_at', $column->name);
        $this->assertEquals('TIMESTAMP', $column->type);
    }

    public function testTimeReturnsDateTimeColumn(): void
    {
        $table = new TableDefinition('schedules');

        $column = $table->time('start_time');

        $this->assertInstanceOf(DateTimeColumn::class, $column);
        $this->assertEquals('start_time', $column->name);
        $this->assertEquals('TIME', $column->type);
    }

    public function testDateReturnsDateTimeColumn(): void
    {
        $table = new TableDefinition('events');

        $column = $table->date('event_date');

        $this->assertInstanceOf(DateTimeColumn::class, $column);
        $this->assertEquals('event_date', $column->name);
        $this->assertEquals('DATE', $column->type);
    }

    public function testIndexReturnsNormalIndex(): void
    {
        $table = new TableDefinition('users');

        $index = $table->index('email');

        $this->assertInstanceOf(NormalIndex::class, $index);
        $this->assertEquals('users', $index->tableName);
        $this->assertEquals('email', $index->columns);
    }

    public function testIndexWithCustomName(): void
    {
        $table = new TableDefinition('users');

        $index = $table->index('email', 'idx_users_email');

        $this->assertInstanceOf(NormalIndex::class, $index);
        $this->assertEquals('idx_users_email', $index->indexName);
    }

    public function testPrimaryReturnsPrimaryIndex(): void
    {
        $table = new TableDefinition('users');

        $index = $table->primary('id');

        $this->assertInstanceOf(PrimaryIndex::class, $index);
        $this->assertEquals('users', $index->tableName);
        $this->assertEquals('id', $index->columns);
    }

    public function testPrimaryWithCompositeKey(): void
    {
        $table = new TableDefinition('user_roles');

        $index = $table->primary(['user_id', 'role_id']);

        $this->assertInstanceOf(PrimaryIndex::class, $index);
    }

    public function testUniqueReturnsUniqueIndex(): void
    {
        $table = new TableDefinition('users');

        $index = $table->unique('email');

        $this->assertInstanceOf(UniqueIndex::class, $index);
        $this->assertEquals('users', $index->tableName);
        $this->assertEquals('email', $index->columns);
    }

    public function testUniqueWithCustomName(): void
    {
        $table = new TableDefinition('users');

        $index = $table->unique('email', 'unique_user_email');

        $this->assertInstanceOf(UniqueIndex::class, $index);
        $this->assertEquals('unique_user_email', $index->indexName);
    }

    public function testSupportsMethodChaining(): void
    {
        $table = new TableDefinition('users');

        $column = $table->string('email', 255);

        // Verify method chaining is supported on returned columns
        $this->assertInstanceOf(StringColumn::class, $column);
    }
}
