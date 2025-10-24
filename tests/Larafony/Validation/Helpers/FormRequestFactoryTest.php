<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Validation\Helpers;

use Larafony\Framework\Http\Factories\ServerRequestFactory;
use Larafony\Framework\Validation\FormRequest;
use Larafony\Framework\Validation\Helpers\FormRequestFactory;
use PHPUnit\Framework\TestCase;

class FormRequestFactoryTest extends TestCase
{
    private FormRequestFactory $factory;
    private ServerRequestFactory $serverRequestFactory;

    protected function setUp(): void
    {
        $this->serverRequestFactory = new ServerRequestFactory();
        $this->factory = new FormRequestFactory($this->serverRequestFactory);
    }

    public function testCreatesFormRequestFromServerRequest(): void
    {
        $source = $this->serverRequestFactory->createServerRequest(
            'POST',
            'https://example.com/test',
            ['SERVER_NAME' => 'example.com']
        );
        $source = $source->withCookieParams(['session' => 'abc123']);

        $formRequest = $this->factory->create(TestFormRequest::class, $source);

        $this->assertInstanceOf(TestFormRequest::class, $formRequest);
        $this->assertSame('POST', $formRequest->getMethod());
        $this->assertSame('https://example.com/test', (string) $formRequest->getUri());
        $this->assertSame(['SERVER_NAME' => 'example.com'], $formRequest->getServerParams());
    }

    public function testCopiesQueryParams(): void
    {
        $source = $this->serverRequestFactory->createServerRequest('GET', '/test')
            ->withQueryParams(['page' => '1', 'limit' => '10']);

        $formRequest = $this->factory->create(TestFormRequest::class, $source);

        $this->assertSame(['page' => '1', 'limit' => '10'], $formRequest->getQueryParams());
    }

    public function testCopiesParsedBody(): void
    {
        $source = $this->serverRequestFactory->createServerRequest('POST', '/test')
            ->withParsedBody(['username' => 'john', 'email' => 'john@example.com']);

        $formRequest = $this->factory->create(TestFormRequest::class, $source);

        $this->assertSame(
            ['username' => 'john', 'email' => 'john@example.com'],
            $formRequest->getParsedBody()
        );
    }

    public function testCopiesHeaders(): void
    {
        $source = $this->serverRequestFactory->createServerRequest('POST', '/test')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Bearer token123');

        $formRequest = $this->factory->create(TestFormRequest::class, $source);

        $this->assertTrue($formRequest->hasHeader('Content-Type'));
        $this->assertSame(['application/json'], $formRequest->getHeader('Content-Type'));
        $this->assertSame(['Bearer token123'], $formRequest->getHeader('Authorization'));
    }

    public function testCopiesAttributes(): void
    {
        $source = $this->serverRequestFactory->createServerRequest('GET', '/test')
            ->withAttribute('route', 'users.create')
            ->withAttribute('user_id', 123);

        $formRequest = $this->factory->create(TestFormRequest::class, $source);

        $this->assertSame('users.create', $formRequest->getAttribute('route'));
        $this->assertSame(123, $formRequest->getAttribute('user_id'));
    }

    public function testCopiesAllDataTogether(): void
    {
        $source = $this->serverRequestFactory->createServerRequest(
            'PUT',
            'https://api.example.com/users/123',
            ['REMOTE_ADDR' => '127.0.0.1']
        );

        $source = $source
            ->withCookieParams(['token' => 'xyz'])
            ->withQueryParams(['include' => 'profile'])
            ->withParsedBody(['name' => 'John Doe'])
            ->withHeader('Accept', 'application/json')
            ->withAttribute('user_id', 123);

        $formRequest = $this->factory->create(TestFormRequest::class, $source);

        $this->assertSame('PUT', $formRequest->getMethod());
        $this->assertSame('https://api.example.com/users/123', (string) $formRequest->getUri());
        $this->assertSame(['include' => 'profile'], $formRequest->getQueryParams());
        $this->assertSame(['name' => 'John Doe'], $formRequest->getParsedBody());
        $this->assertSame(['application/json'], $formRequest->getHeader('Accept'));
        $this->assertSame(123, $formRequest->getAttribute('user_id'));
    }

    public function testSkipsNonExistentPropertiesWhenCopying(): void
    {
        // Create a FormRequest with extra properties that don't exist in ServerRequest
        $source = $this->serverRequestFactory->createServerRequest('POST', '/test')
            ->withParsedBody(['data' => 'test']);

        // This will trigger the catch block in copyProperties because
        // FormRequestWithExtraProperties might have properties that don't exist in base ServerRequest
        $formRequest = $this->factory->create(FormRequestWithExtraProperties::class, $source);

        // Should still work despite property mismatch
        $this->assertInstanceOf(FormRequestWithExtraProperties::class, $formRequest);
        $this->assertSame('POST', $formRequest->getMethod());
        $this->assertSame(['data' => 'test'], $formRequest->getParsedBody());
    }
}

class TestFormRequest extends FormRequest {}

class FormRequestWithExtraProperties extends FormRequest
{
    public string $customProperty = 'default';
    public ?int $extraField = null;
}
