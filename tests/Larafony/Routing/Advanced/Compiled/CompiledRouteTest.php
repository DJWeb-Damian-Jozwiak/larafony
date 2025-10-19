<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Advanced\Compiled;

use Larafony\Framework\Routing\Advanced\Compiled\CompiledRoute;
use Larafony\Framework\Tests\TestCase;

class CompiledRouteTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $compiled = new CompiledRoute(
            '#^/users/(?<id>\d+)$#u',
            ['id'],
            ['id' => '\d+']
        );

        $this->assertSame('#^/users/(?<id>\d+)$#u', $compiled->regex);
        $this->assertSame(['id'], $compiled->variables);
        $this->assertSame(['id' => '\d+'], $compiled->patterns);
    }

    public function testMatchReturnsParametersForMatchingPath(): void
    {
        $compiled = new CompiledRoute(
            '#^/users/(?<id>\d+)$#u',
            ['id'],
            ['id' => '\d+']
        );

        $result = $compiled->match('/users/123');

        $this->assertSame(['id' => '123'], $result);
    }

    public function testMatchReturnsNullForNonMatchingPath(): void
    {
        $compiled = new CompiledRoute(
            '#^/users/(?<id>\d+)$#u',
            ['id'],
            ['id' => '\d+']
        );

        $result = $compiled->match('/users/abc');

        $this->assertNull($result);
    }

    public function testMatchHandlesMultipleParameters(): void
    {
        $compiled = new CompiledRoute(
            '#^/users/(?<userId>\d+)/posts/(?<postId>\d+)$#u',
            ['userId', 'postId'],
            ['userId' => '\d+', 'postId' => '\d+']
        );

        $result = $compiled->match('/users/1/posts/42');

        $this->assertSame(['userId' => '1', 'postId' => '42'], $result);
    }

    public function testMatchHandlesComplexPatterns(): void
    {
        $compiled = new CompiledRoute(
            '#^/posts/(?<slug>[a-z-]+)$#u',
            ['slug'],
            ['slug' => '[a-z-]+']
        );

        $result = $compiled->match('/posts/hello-world');

        $this->assertSame(['slug' => 'hello-world'], $result);
    }

    public function testIsReadonly(): void
    {
        $reflection = new \ReflectionClass(CompiledRoute::class);

        $this->assertTrue($reflection->isReadOnly());
    }
}
