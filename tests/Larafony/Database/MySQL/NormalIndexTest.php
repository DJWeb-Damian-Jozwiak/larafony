<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database\MySQL;

use Larafony\Framework\Database\Drivers\MySQL\Schema\IndexDefinitions\NormalIndex;
use Larafony\Framework\Tests\TestCase;

class NormalIndexTest extends TestCase
{
    public function testCreatesNormalIndex(): void
    {
        $index = new NormalIndex('posts', 'user_id', 'posts_user_id_index');

        $this->assertEquals('posts', $index->tableName);
        $this->assertEquals('posts_user_id_index', $index->indexName);
        $this->assertEquals('user_id', $index->columns);
    }

    public function testGeneratesSqlDefinition(): void
    {
        $index = new NormalIndex('posts', 'user_id', 'posts_user_id_index');
        $sql = $index->getSqlDefinition();

        $this->assertStringContainsString('CREATE INDEX', $sql);
        $this->assertStringContainsString('posts_user_id_index', $sql);
        $this->assertStringContainsString('ON posts', $sql);
        $this->assertStringContainsString('user_id', $sql);
    }

    public function testSupportsMultipleColumns(): void
    {
        $index = new NormalIndex('logs', 'user_id, created_at', 'logs_user_date_index');
        $sql = $index->getSqlDefinition();

        $this->assertStringContainsString('CREATE INDEX', $sql);
        $this->assertStringContainsString('logs_user_date_index', $sql);
        $this->assertStringContainsString('ON logs', $sql);
        $this->assertStringContainsString('user_id, created_at', $sql);
    }
}
