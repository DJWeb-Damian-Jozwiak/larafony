<?php

namespace Larafony\Framework\Tests\Http\Factories;

use Larafony\Framework\Http\Factories\ServerRequestFactory;
use Larafony\Framework\Tests\Http\Helpers\MockPhpStreamWrapper;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

class ServerRequestFactoryTest extends TestCase
{
    private ServerRequestFactory $factory;
    private ServerRequestInterface $request;

    protected function setUp(): void
    {
        $this->factory = new ServerRequestFactory();
        $this->request = $this->factory->createServerRequest('GET', '/test');
    }

    protected function tearDown(): void
    {
        $_SERVER = [];
        $_GET = [];
        $_POST = [];
        $_FILES = [];
        $_COOKIE = [];
        $_REQUEST = [];

        if (in_array('php', stream_get_wrappers())) {
            stream_wrapper_unregister('php');
            stream_wrapper_restore('php');
        }

        parent::tearDown();
    }

    public function testCreateServerRequestReturnsServerRequestInstance(): void
    {
        $request = $this->factory->createServerRequest('GET', '/test');

        $this->assertInstanceOf(ServerRequestInterface::class, $request);
        $this->assertEquals('GET', $request->getMethod());
        $this->assertTrue($request->isGet());
        $this->assertEquals('/test', $request->getUri()->getPath());
    }

    public function testCreateServerRequestAcceptsUriInterface(): void
    {
        $uri = $this->createStub(UriInterface::class);
        $request = $this->factory->createServerRequest('POST', $uri);
        $this->assertTrue($request->isPost());

        $this->assertSame($uri, $request->getUri());
        $this->assertEquals('POST', $request->getMethod());
    }

    public function testCreateServerRequestWithServerParams(): void
    {
        $serverParams = ['SERVER_NAME' => 'example.com', 'SERVER_PORT' => '443'];
        $request = $this->factory->createServerRequest('GET', '/test', $serverParams);

        $this->assertEquals($serverParams, $request->getServerParams());
    }

    public function testCreateFromGlobalsReturnsServerRequestWithCorrectMethod(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['test' => 'value'];
        $request = $this->factory->createServerRequestFromGlobals();

        $this->assertTrue($request->has('test'));
        $this->assertEquals('value', $request->input('test'));
        $this->assertEquals('value', $request->post('test'));
        $this->assertEquals('POST', $request->getMethod());
    }

    public function testCreateFromGlobalsDefaultsToGetMethod(): void
    {
        unset($_SERVER['REQUEST_METHOD']);
        $request = $this->factory->createServerRequestFromGlobals();

        $this->assertEquals('GET', $request->getMethod());
    }

    public function testCreateFromGlobalsHandlesFormUrlEncodedData(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_POST = ['test' => 'value'];

        $request = $this->factory->createServerRequestFromGlobals();

        $this->assertEquals($_POST, $request->getParsedBody());
    }

    public function testCreateFromGlobalsHandlesQueryParams(): void
    {
        $_GET = ['page' => '1', 'sort' => 'desc'];
        $request = $this->factory->createServerRequestFromGlobals();
        $this->assertEquals('1', $request->input('page'));
        $this->assertEquals('1', $request->query('page'));
        $this->assertEquals($_GET, $request->all());

        $this->assertEquals($_GET, $request->getQueryParams());
    }

    public function testCreateFromGlobalsHandlesCookies(): void
    {
        $_COOKIE = ['session' => 'abc123'];
        $request = $this->factory->createServerRequestFromGlobals();

        $this->assertEquals($_COOKIE, $request->getCookieParams());
    }

    public function testWithCookieParams(): void
    {
        $COOKIE = ['session' => 'abc123'];
        $request = $this->factory->createServerRequestFromGlobals();
        $new = $request->withCookieParams($COOKIE);

        $this->assertEquals($COOKIE, $new->getCookieParams());
        $this->assertEquals([], $request->getCookieParams());
    }

    public function testCreateFromGlobalsHandlesUploadedFiles(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        touch('/tmp/phpXXXXXX');
        $_FILES = [
            'file' => [
                'name' => 'test.txt',
                'type' => 'text/plain',
                'tmp_name' => '/tmp/phpXXXXXX',
                'error' => UPLOAD_ERR_OK,
                'size' => 123
            ]
        ];

        $request = $this->factory->createServerRequestFromGlobals();
        $uploadedFiles = $request->getUploadedFiles();

        $this->assertNotEmpty($uploadedFiles);
        $this->assertArrayHasKey('file', $uploadedFiles);
        unlink('/tmp/phpXXXXXX');
    }

    public function testWithQueryParamsReturnsNewInstance(): void
    {
        $originalRequest = $this->request;
        $newParams = ['page' => '1', 'sort' => 'desc'];

        $newRequest = $originalRequest->withQueryParams($newParams);

        $this->assertNotSame($originalRequest, $newRequest);
        $this->assertEquals($newParams, $newRequest->getQueryParams());
        $this->assertEmpty($originalRequest->getQueryParams());
    }

    public function testWithUploadedFilesReturnsNewInstance(): void
    {
        $originalRequest = $this->request;
        $file = $this->createStub(UploadedFileInterface::class);
        $files = ['avatar' => $file];

        $newRequest = $originalRequest->withUploadedFiles($files);

        $this->assertNotSame($originalRequest, $newRequest);
        $this->assertSame($files, $newRequest->getUploadedFiles());
        $this->assertEmpty($originalRequest->getUploadedFiles());
    }

    public function testWithParsedBodyReturnsNewInstance(): void
    {
        $originalRequest = $this->request;
        $parsedBody = ['username' => 'john_doe'];

        $newRequest = $originalRequest->withParsedBody($parsedBody);

        $this->assertNotSame($originalRequest, $newRequest);
        $this->assertEquals($parsedBody, $newRequest->getParsedBody());
        $this->assertEmpty($originalRequest->getParsedBody());
    }

    public function testWithParsedBodyAcceptsNull(): void
    {
        $request = $this->request->withParsedBody(['test' => 'value']);
        $newRequest = $request->withParsedBody(null);

        $this->assertEmpty($newRequest->getParsedBody());
    }

    public function testGetAttributesReturnsEmptyArrayByDefault(): void
    {
        $this->assertEquals([], $this->request->getAttributes());
    }

    public function testGetAttributeReturnsDefaultValueWhenAttributeDoesNotExist(): void
    {
        $default = 'default_value';
        $this->assertEquals($default, $this->request->getAttribute('non_existent', $default));
    }

    public function testGetAttributeReturnsNullWhenAttributeDoesNotExistAndNoDefaultProvided(): void
    {
        $this->assertNull($this->request->getAttribute('non_existent'));
    }

    public function testWithAttributeReturnsNewInstance(): void
    {
        $originalRequest = $this->request;
        $newRequest = $originalRequest->withAttribute('user_id', 123);

        $this->assertNotSame($originalRequest, $newRequest);
        $this->assertEquals(123, $newRequest->getAttribute('user_id'));
        $this->assertNull($originalRequest->getAttribute('user_id'));
    }

    public function testWithAttributeOverwritesExistingValue(): void
    {
        $request = $this->request->withAttribute('version', 1);
        $newRequest = $request->withAttribute('version', 2);

        $this->assertEquals(2, $newRequest->getAttribute('version'));
    }

    public function testWithoutAttributeReturnsNewInstance(): void
    {
        $request = $this->request
            ->withAttribute('user_id', 123)
            ->withAttribute('role', 'admin');

        $newRequest = $request->withoutAttribute('user_id');

        $this->assertNotSame($request, $newRequest);
        $this->assertNull($newRequest->getAttribute('user_id'));
        $this->assertEquals('admin', $newRequest->getAttribute('role'));
    }

    public function testWithoutAttributeDoesNothingWhenAttributeDoesNotExist(): void
    {
        $request = $this->request->withAttribute('test', 'value');
        $newRequest = $request->withoutAttribute('non_existent');

        $this->assertEquals($request->getAttributes(), $newRequest->getAttributes());
    }

    public function testAttributesWorkAsACollection(): void
    {
        $request = $this->request
            ->withAttribute('user_id', 123)
            ->withAttribute('role', 'admin')
            ->withAttribute('version', 2);

        $expectedAttributes = [
            'user_id' => 123,
            'role' => 'admin',
            'version' => 2
        ];

        $this->assertEquals($expectedAttributes, $request->getAttributes());

        $requestWithoutRole = $request->withoutAttribute('role');
        unset($expectedAttributes['role']);

        $this->assertEquals($expectedAttributes, $requestWithoutRole->getAttributes());
    }

    public function testCreateFromGlobalsHandlesJsonData(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $jsonData = ['test' => 'value'];

        $this->mockPhpInput(json_encode($jsonData));

        $request = $this->factory->createServerRequestFromGlobals();

        $this->assertEquals($jsonData, $request->getParsedBody());

        // Restore php wrapper
        stream_wrapper_restore('php');
    }

    private function mockPhpInput(string $content): void
    {
        stream_wrapper_unregister('php');
        stream_wrapper_register('php', \Larafony\Framework\Tests\Http\Helpers\MockPhpStreamWrapper::class);
        MockPhpStreamWrapper::$content = $content;
    }
}