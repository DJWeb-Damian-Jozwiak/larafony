<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database\MySQL;

use Larafony\Framework\Database\Drivers\MySQL\Schema\IndexDefinitions\UniqueIndex;
use Larafony\Framework\Tests\TestCase;

class UniqueIndexTest extends TestCase
{
    public function testCreatesUniqueIndex(): void
    {
        $index = new UniqueIndex('users', 'email', 'users_email_unique');

        $this->assertEquals('users', $index->tableName);
        $this->assertEquals('users_email_unique', $index->indexName);
        $this->assertEquals('email', $index->columns);
    }

    public function testGeneratesSqlDefinition(): void
    {
        $index = new UniqueIndex('users', 'email', 'users_email_unique');
        $sql = $index->getSqlDefinition();

        $this->assertStringContainsString('CREATE UNIQUE INDEX', $sql);
        $this->assertStringContainsString('users_email_unique', $sql);
        $this->assertStringContainsString('ON users', $sql);
        $this->assertStringContainsString('email', $sql);
    }

    public function testSupportsMultipleColumns(): void
    {
        $index = new UniqueIndex('orders', 'user_id, product_id', 'orders_user_product_unique');
        $sql = $index->getSqlDefinition();

        $this->assertStringContainsString('CREATE UNIQUE INDEX', $sql);
        $this->assertStringContainsString('orders_user_product_unique', $sql);
        $this->assertStringContainsString('ON orders', $sql);
        $this->assertStringContainsString('user_id, product_id', $sql);
    }
}
