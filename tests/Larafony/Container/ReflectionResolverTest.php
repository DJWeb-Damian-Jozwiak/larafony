<?php

declare(strict_types=1);

namespace Larafony\Container;

use Larafony\Framework\Container\Exceptions\ContainerError;
use Larafony\Framework\Container\Resolvers\ReflectionResolver;
use PHPUnit\Framework\TestCase;
use ReflectionParameter;

final class ReflectionResolverTest extends TestCase
{
    private ReflectionResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ReflectionResolver();
    }

    public function testGetConstructorParameters(): void
    {
        $parameters = $this->resolver->getConstructorParameters(TestClassWithParams::class);

        $this->assertCount(2, $parameters);
        $this->assertInstanceOf(ReflectionParameter::class, $parameters[0]);
    }

    public function testGetParameterType(): void
    {
        $parameters = $this->resolver->getConstructorParameters(TestClassWithParams::class);

        $type = $this->resolver->getParameterType($parameters[0]);
        $this->assertSame('string', $type);
    }

    public function testHasDefaultValue(): void
    {
        $parameters = $this->resolver->getConstructorParameters(TestClassWithDefaults::class);

        $this->assertFalse($this->resolver->hasDefaultValue($parameters[0]));
        $this->assertTrue($this->resolver->hasDefaultValue($parameters[1]));
    }

    public function testGetDefaultValue(): void
    {
        $parameters = $this->resolver->getConstructorParameters(TestClassWithDefaults::class);

        $this->assertSame('default', $this->resolver->getDefaultValue($parameters[1]));
    }

    public function testGetDefaultValueThrowsException(): void
    {
        $parameters = $this->resolver->getConstructorParameters(TestClassWithDefaults::class);

        $this->expectException(ContainerError::class);
        $this->expectExceptionMessage('has no default value');

        $this->resolver->getDefaultValue($parameters[0]);
    }

    public function testAllowsNull(): void
    {
        $parameters = $this->resolver->getConstructorParameters(TestClassWithNullable::class);

        $this->assertTrue($this->resolver->allowsNull($parameters[0]));
        $this->assertFalse($this->resolver->allowsNull($parameters[1]));
    }

    public function testGetDefaultValueForBuiltInType(): void
    {
        $this->assertSame(0, $this->resolver->getDefaultValueForBuiltInType('int'));
        $this->assertSame(0.0, $this->resolver->getDefaultValueForBuiltInType('float'));
        $this->assertSame('', $this->resolver->getDefaultValueForBuiltInType('string'));
        $this->assertSame(false, $this->resolver->getDefaultValueForBuiltInType('bool'));
        $this->assertSame([], $this->resolver->getDefaultValueForBuiltInType('array'));
        $this->assertNull($this->resolver->getDefaultValueForBuiltInType('unknown'));
    }
}

// Test helpers
class TestClassWithParams
{
    public function __construct(
        public string $name,
        public int $age,
    ) {
    }
}

class TestClassWithDefaults
{
    public function __construct(
        public string $required,
        public string $optional = 'default',
    ) {
    }
}

class TestClassWithNullable
{
    public function __construct(
        public ?string $nullable,
        public string $required,
    ) {
    }
}