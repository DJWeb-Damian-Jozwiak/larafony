<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Web;


use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Http\Factories\StreamFactory;
use Larafony\Framework\Http\ServiceProviders\HttpServiceProvider;
use Larafony\Framework\Web\Application;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    public function testApplication()
    {
        Application::empty();
        $app = Application::instance('/tmp');
        $app->withServiceProviders([HttpServiceProvider::class]);
        $this->assertEquals('/tmp', $app->getBinding('base_path'));
        $body = new StreamFactory()->createStream('Hello, World!');
        ob_start();
        $response = new ResponseFactory()->createResponse()->withBody($body)->withHeader('test', 'test');
        $app->emit($response);
        $expected = 'Hello, World!';
        $result = ob_get_clean();
        $this->assertEquals($expected, $result);

    }
}