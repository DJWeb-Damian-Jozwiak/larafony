<?php

declare(strict_types=1);

namespace Larafony\Console;

use Larafony\Framework\Console\Application;
use Larafony\Framework\Console\Formatters\OutputFormatter;
use Larafony\Framework\Console\ServiceProviders\ConsoleServiceProvider;
use Larafony\Framework\Http\ServiceProviders\HttpServiceProvider;
use Larafony\Framework\Tests\TestCase;
use Psr\Http\Message\StreamInterface;

class ConsoleApplicationTest extends TestCase
{
    public function testConsoleApplication(): void
    {
        $basePath = dirname(__DIR__, 2) . '/public';
        $app = Application::instance($basePath);
        $app->withServiceProviders([
            HttpServiceProvider::class,
            ConsoleServiceProvider::class
        ]);
        $outputStream = $this->createStub(StreamInterface::class);
        $input = $this->createStub(StreamInterface::class);
        $app->set('output_stream', $outputStream);
        $app->set('input_stream', $input);

        $this->assertTrue($app->has(OutputFormatter::class));
        $output = $app->handle(['bin/larafony', 'test:command']);
        $this->assertSame(0, $output);
    }
}