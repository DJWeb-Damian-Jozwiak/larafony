<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database\MySQL;

use Larafony\Framework\Database\Drivers\MySQL\Grammar;
use Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions\IntColumn;
use Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions\StringColumn;
use Larafony\Framework\Database\Drivers\MySQL\Schema\TableDefinition;
use Larafony\Framework\Tests\TestCase;

class GrammarTest extends TestCase
{
    private Grammar $grammar;

    protected function setUp(): void
    {
        parent::setUp();
        $this->grammar = new Grammar();
    }

    public function testCompilesCreateTable(): void
    {
        $table = new TableDefinition('users', [
            'id' => new IntColumn('id', type: 'INT'),
            'name' => new StringColumn('name', length: 255, type: 'VARCHAR'),
        ]);

        $sql = $this->grammar->compileCreate($table);

        $this->assertStringContainsString('CREATE TABLE users', $sql);
        $this->assertStringContainsString('id', $sql);
        $this->assertStringContainsString('name', $sql);
    }

    public function testCompilesAddColumns(): void
    {
        $table = new TableDefinition('users', [
            'email' => new StringColumn('email', length: 255, type: 'VARCHAR'),
        ]);

        $sql = $this->grammar->compileAddColumns($table);

        $this->assertStringContainsString('ALTER TABLE users', $sql);
        $this->assertStringContainsString('ADD COLUMN', $sql);
        $this->assertStringContainsString('email', $sql);
    }

    public function testCompilesAddColumnsReturnsEmptyForNoColumns(): void
    {
        $table = new TableDefinition('users', []);

        $sql = $this->grammar->compileAddColumns($table);

        $this->assertEmpty($sql);
    }

    public function testCompilesModifyColumns(): void
    {
        $emailColumn = new StringColumn('email', length: 100, type: 'VARCHAR');
        $emailColumn->change();

        $table = new TableDefinition('users', [
            'email' => $emailColumn,
        ]);

        $sql = $this->grammar->compileModifyColumns($table);

        $this->assertStringContainsString('ALTER TABLE users', $sql);
        $this->assertStringContainsString('MODIFY COLUMN', $sql);
        $this->assertStringContainsString('email', $sql);
    }

    public function testCompilesModifyColumnsReturnsEmptyForNoModifiedColumns(): void
    {
        $table = new TableDefinition('users', [
            'email' => new StringColumn('email', length: 255, type: 'VARCHAR'),
        ]);

        $sql = $this->grammar->compileModifyColumns($table);

        $this->assertEmpty($sql);
    }

    public function testCompilesDropColumns(): void
    {
        $emailColumn = new StringColumn('email', length: 255, type: 'VARCHAR');
        $emailColumn->delete();

        $table = new TableDefinition('users', [
            'email' => $emailColumn,
        ]);

        $sql = $this->grammar->compileDropColumns($table);

        $this->assertStringContainsString('ALTER TABLE users', $sql);
        $this->assertStringContainsString('DROP COLUMN email', $sql);
    }

    public function testCompilesDropColumnsReturnsEmptyForNoDeletedColumns(): void
    {
        $table = new TableDefinition('users', [
            'email' => new StringColumn('email', length: 255, type: 'VARCHAR'),
        ]);

        $sql = $this->grammar->compileDropColumns($table);

        $this->assertEmpty($sql);
    }

    public function testCompilesDropTable(): void
    {
        $sql = $this->grammar->compileDropTable('users', false);

        $this->assertStringContainsString('DROP TABLE', $sql);
        $this->assertStringContainsString('users', $sql);
    }

    public function testCompilesDropTableIfExists(): void
    {
        $sql = $this->grammar->compileDropTable('users', true);

        $this->assertStringContainsString('DROP TABLE', $sql);
        $this->assertStringContainsString('IF EXISTS', $sql);
        $this->assertStringContainsString('users', $sql);
    }
}
