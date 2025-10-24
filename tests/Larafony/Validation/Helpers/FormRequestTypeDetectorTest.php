<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Validation\Helpers;

use Larafony\Framework\Validation\FormRequest;
use Larafony\Framework\Validation\Helpers\FormRequestTypeDetector;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionMethod;

class FormRequestTypeDetectorTest extends TestCase
{
    private FormRequestTypeDetector $detector;

    protected function setUp(): void
    {
        $this->detector = new FormRequestTypeDetector();
    }

    public function testDetectsFormRequestParameter(): void
    {
        $controller = new class {
            public function handle(ConcreteFormRequest $request) {}
        };

        $method = new ReflectionMethod($controller, 'handle');
        $result = $this->detector->detect($method);

        $this->assertSame(ConcreteFormRequest::class, $result);
    }

    public function testReturnsNullForServerRequest(): void
    {
        $controller = new class {
            public function handle(ServerRequestInterface $request) {}
        };

        $method = new ReflectionMethod($controller, 'handle');
        $result = $this->detector->detect($method);

        $this->assertNull($result);
    }

    public function testReturnsNullForNoParameters(): void
    {
        $controller = new class {
            public function handle() {}
        };

        $method = new ReflectionMethod($controller, 'handle');
        $result = $this->detector->detect($method);

        $this->assertNull($result);
    }

    public function testReturnsNullForUnionType(): void
    {
        $controller = new class {
            public function handle(ConcreteFormRequest|ServerRequestInterface $request) {}
        };

        $method = new ReflectionMethod($controller, 'handle');
        $result = $this->detector->detect($method);

        $this->assertNull($result);
    }

    public function testReturnsNullForNonFormRequestClass(): void
    {
        $controller = new class {
            public function handle(\stdClass $request) {}
        };

        $method = new ReflectionMethod($controller, 'handle');
        $result = $this->detector->detect($method);

        $this->assertNull($result);
    }

    public function testOnlyChecksFirstParameter(): void
    {
        $controller = new class {
            public function handle(ServerRequestInterface $request, ConcreteFormRequest $formRequest) {}
        };

        $method = new ReflectionMethod($controller, 'handle');
        $result = $this->detector->detect($method);

        $this->assertNull($result);
    }
}

class ConcreteFormRequest extends FormRequest {}
