<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Advanced\Decorators;

use Larafony\Framework\Routing\Advanced\Decorators\ParsedRouteDecorator;
use Larafony\Framework\Routing\Advanced\RouteParameter;
use Larafony\Framework\Tests\TestCase;

class ParsedRouteDecoratorTest extends TestCase
{
    public function testParsesSimpleParameter(): void
    {
        $decorator = new ParsedRouteDecorator('/users/<id>');

        $this->assertArrayHasKey('id', $decorator->definitions);
        $this->assertInstanceOf(RouteParameter::class, $decorator->definitions['id']);
        $this->assertSame('id', $decorator->definitions['id']->name);
        $this->assertSame('[^/]+', $decorator->definitions['id']->pattern);
    }

    public function testParsesParameterWithPattern(): void
    {
        $decorator = new ParsedRouteDecorator('/users/<id:\d+>');

        $this->assertArrayHasKey('id', $decorator->definitions);
        $this->assertSame('id', $decorator->definitions['id']->name);
        $this->assertSame('\d+', $decorator->definitions['id']->pattern);
    }

    public function testParsesMultipleParameters(): void
    {
        $decorator = new ParsedRouteDecorator('/posts/<category:[a-z]+>/<slug:[a-z-]+>');

        $this->assertCount(2, $decorator->definitions);
        $this->assertArrayHasKey('category', $decorator->definitions);
        $this->assertArrayHasKey('slug', $decorator->definitions);
        $this->assertSame('category', $decorator->definitions['category']->name);
        $this->assertSame('[a-z]+', $decorator->definitions['category']->pattern);
        $this->assertSame('slug', $decorator->definitions['slug']->name);
        $this->assertSame('[a-z-]+', $decorator->definitions['slug']->pattern);
    }

    public function testPathWithoutParametersReturnsEmptyArray(): void
    {
        $decorator = new ParsedRouteDecorator('/users');

        $this->assertEmpty($decorator->definitions);
    }

    public function testMixedPathWithStaticAndDynamicSegments(): void
    {
        $decorator = new ParsedRouteDecorator('/api/v1/users/<id:\d+>/posts/<postId:\d+>');

        $this->assertCount(2, $decorator->definitions);
        $this->assertArrayHasKey('id', $decorator->definitions);
        $this->assertArrayHasKey('postId', $decorator->definitions);
    }
}
