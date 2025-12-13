<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Validation\Handlers;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Exceptions\Validation\ValidationFailed;
use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Http\Factories\ServerRequestFactory;
use Larafony\Framework\Validation\Attributes\Email;
use Larafony\Framework\Validation\Attributes\IsValidated;
use Larafony\Framework\Validation\Attributes\Required;
use Larafony\Framework\Validation\FormRequest;
use Larafony\Framework\Validation\Handlers\FormRequestAwareHandler;
use Larafony\Framework\Web\Application;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class FormRequestAwareHandlerTest extends TestCase
{
    private ContainerContract $container;
    private ServerRequestFactory $requestFactory;

    protected function setUp(): void
    {
        $this->container = Application::instance();
        $this->requestFactory = new ServerRequestFactory();
    }

    protected function tearDown(): void
    {
        Application::empty();
    }

    public function testHandlesRegularServerRequest(): void
    {
        $controller = new TestController();
        $this->container->set(TestController::class, $controller);

        $handler = new FormRequestAwareHandler(TestController::class, 'handleRegular', $this->container);
        $request = $this->requestFactory->createServerRequest("GET", "/test");

        $response = $handler->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testCreatesAndValidatesFormRequest(): void
    {
        $controller = new TestController();
        $this->container->set(TestController::class, $controller);

        $handler = new FormRequestAwareHandler(
            TestController::class,
            'handleFormRequest',
            $this->container
        );

        $request = $this->requestFactory->createServerRequest("GET", "/test")
            ->withParsedBody(['email' => 'test@example.com', 'name' => 'John Doe']);

        $response = $handler->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testThrowsValidationExceptionOnInvalidData(): void
    {
        $controller = new TestController();
        $this->container->set(TestController::class, $controller);

        $handler = new FormRequestAwareHandler(
            TestController::class,
            'handleFormRequest',
            $this->container
        );

        $request = ($this->requestFactory->createServerRequest("GET", "/test"))
            ->withParsedBody(['email' => 'invalid-email']);

        $this->expectException(ValidationFailed::class);
        $this->expectExceptionCode(422);

        $handler->handle($request);
    }

    public function testPopulatesFormRequestProperties(): void
    {
        $controller = new TestController();
        $this->container->set(TestController::class, $controller);

        $handler = new FormRequestAwareHandler(
            TestController::class,
            'handleFormRequest',
            $this->container
        );

        $request = ($this->requestFactory->createServerRequest("GET", "/test"))
            ->withParsedBody(['email' => 'john@example.com', 'name' => 'John Doe']);

        $response = $handler->handle($request);

        // Controller stores the request for testing
        $this->assertSame('john@example.com', $controller->lastRequest->email);
        $this->assertSame('John Doe', $controller->lastRequest->name);
    }

    public function testHandlesMethodWithNoParameters(): void
    {
        $controller = new TestController();
        $this->container->set(TestController::class, $controller);

        $handler = new FormRequestAwareHandler(
            TestController::class,
            'handleNoParams',
            $this->container
        );

        $request = $this->requestFactory->createServerRequest("GET", "/test");
        $response = $handler->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(204, $response->getStatusCode());
    }

    public function testResolvesControllerFromContainer(): void
    {
        // Controller not yet in container
        $this->container->bind(TestController::class, TestController::class);

        $handler = new FormRequestAwareHandler(
            TestController::class,
            'handleRegular',
            $this->container
        );

        $request = $this->requestFactory->createServerRequest("GET", "/test");
        $response = $handler->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}

class TestController
{
    public ?FormRequest $lastRequest = null;
    private ResponseFactory $responseFactory;

    public function __construct()
    {
        $this->responseFactory = new ResponseFactory();
    }

    public function handleRegular(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory->createResponse(200);
    }

    public function handleFormRequest(TestValidatedRequest $request): ResponseInterface
    {
        $this->lastRequest = $request;
        return $this->responseFactory->createResponse(201);
    }

    public function handleNoParams(): ResponseInterface
    {
        return $this->responseFactory->createResponse(204);
    }
}

class TestValidatedRequest extends FormRequest
{
    #[Email]
    #[Required]
    #[IsValidated]
    public ?string $email = null;

    #[Required]
    #[IsValidated]
    public ?string $name = null;
}
