<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Log;

use Larafony\Framework\Log\Context;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Context::class)]
final class ContextTest extends TestCase
{
    #[Test]
    public function it_can_be_created_with_empty_data(): void
    {
        $context = new Context();

        $this->assertSame([], $context->all());
    }

    #[Test]
    public function it_can_be_created_with_initial_data(): void
    {
        $data = ['user_id' => 123, 'action' => 'login'];
        $context = new Context($data);

        $this->assertSame($data, $context->all());
    }

    #[Test]
    public function it_can_get_value_by_key(): void
    {
        $context = new Context(['name' => 'John', 'age' => 30]);

        $this->assertSame('John', $context->get('name'));
        $this->assertSame(30, $context->get('age'));
    }

    #[Test]
    public function it_returns_null_for_non_existent_key(): void
    {
        $context = new Context(['name' => 'John']);

        $this->assertNull($context->get('email'));
    }

    #[Test]
    public function it_can_check_if_key_exists(): void
    {
        $context = new Context(['name' => 'John', 'email' => null]);

        $this->assertTrue($context->has('name'));
        $this->assertTrue($context->has('email'));
        $this->assertFalse($context->has('phone'));
    }

    #[Test]
    public function it_returns_all_data(): void
    {
        $data = ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'];
        $context = new Context($data);

        $this->assertSame($data, $context->all());
    }

    #[Test]
    public function it_handles_nested_arrays(): void
    {
        $data = [
            'user' => [
                'id' => 123,
                'name' => 'John',
                'roles' => ['admin', 'user'],
            ],
        ];
        $context = new Context($data);

        $this->assertSame($data['user'], $context->get('user'));
        $this->assertTrue($context->has('user'));
    }
}
