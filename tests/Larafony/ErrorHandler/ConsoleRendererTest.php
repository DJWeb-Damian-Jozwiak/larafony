<?php

declare(strict_types=1);

namespace Larafony\ErrorHandler;

use Larafony\Framework\Console\Application;
use Larafony\Framework\Console\Contracts\OutputContract;
use Larafony\Framework\ErrorHandler\Handlers\ConsoleHandler;
use Larafony\Framework\ErrorHandler\Renderers\ConsoleRenderer;
use Larafony\Framework\ErrorHandler\Renderers\Helpers\DebugSession;
use Larafony\Framework\ErrorHandler\Renderers\Partials\ConsoleCommandProcessor;
use Larafony\Framework\ErrorHandler\Renderers\Partials\ConsoleRendererFactory;
use Larafony\Framework\ErrorHandler\TraceCollection;
use Larafony\Framework\Tests\Helpers\TestSequenceCompletedException;
use Larafony\Framework\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;

class ConsoleRendererTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testRenderHelp()
    {
        $commands = ['help'];
        $currentCommand = 0;
        $expectedOutput = [];

        $output = $this->createMock(OutputContract::class);
        $output->method('question')
            ->willReturnCallback(function () use ($commands, &$currentCommand) {
                if ($currentCommand >= count($commands)) {
                    throw new TestSequenceCompletedException();
                }
                return $commands[$currentCommand++];
            });

        $output->expects($this->once())
            ->method('info')
            ->with('Available commands:');

        $output
            ->expects($this->exactly(10))
            ->method('writeln')
            ->willReturnCallback(function($message) use (&$expectedOutput) {
                $expectedOutput[] = $message;
            });

        $app = Application::instance();
        $app->set(OutputContract::class, $output);
        $renderer = new ConsoleRendererFactory($app)->create();

        $this->executeWithExceptionCatch(function () use ($renderer) {
            $renderer->render(new RuntimeException('Test exception'));
        });

        $this->assertEquals([
            '',
            '<danger>Test exception</danger>',
            '',
            '  trace      Show full stack trace',
            '  frame N    Show details of frame N',
            '  vars N     Show local variables in frame N',
            '  source N   Show more source code for frame N',
            '  env        Show environment details',
            '  help       Show this help message',
            '  exit       Exit interactive debugger'
        ], $expectedOutput);
    }

    public function testRenderTrace()
    {
        $expectedOutput = [];
        $output = $this->createStub(OutputContract::class);
        $commands = ['trace'];
        $currentCommand = 0;
        $output->method('question')
            ->willReturnCallback(function () use ($commands, &$currentCommand) {
                if ($currentCommand >= count($commands)) {
                    throw new TestSequenceCompletedException();
                }
                return $commands[$currentCommand++];
            });
        $output->method('writeln')
            ->willReturnCallback(function($message) use (&$expectedOutput) {
                $expectedOutput[] = $message;
            });

        $app = Application::instance();
        $app->set(OutputContract::class, $output);
        $renderer = new ConsoleRendererFactory($app)->create();

        $this->executeWithExceptionCatch(function () use ($renderer) {
            $renderer->render(new RuntimeException('Test exception'));
        });

        $this->assertTrue(in_array('#0 (__construct)', $expectedOutput));
    }

    public function testRenderFrame()
    {
        $expectedOutput = [];
        $output = $this->createStub(OutputContract::class);
        $commands = ['frame 0'];
        $currentCommand = 0;
        $output->method('question')
            ->willReturnCallback(function () use ($commands, &$currentCommand) {
                if ($currentCommand >= count($commands)) {
                    throw new TestSequenceCompletedException();
                }
                return $commands[$currentCommand++];
            });
        $output->method('writeln')
            ->willReturnCallback(function($message) use (&$expectedOutput) {
                $expectedOutput[] = $message;
            });

        $app = Application::instance();
        $app->set(OutputContract::class, $output);
        $renderer = new ConsoleRendererFactory($app)->create();

        $this->executeWithExceptionCatch(function () use ($renderer) {
            $renderer->render(new RuntimeException('Test exception'));
        });


        $this->assertTrue(in_array('Call: __construct()', $expectedOutput));
    }

    public function testRenderInvalidFrame()
    {
        $commands = ['frame 1000'];
        $currentCommand = 0;
        $expectedOutput = [];

        $output = $this->createMock(OutputContract::class);
        $output->method('question')
            ->willReturnCallback(function () use ($commands, &$currentCommand) {
                if ($currentCommand >= count($commands)) {
                    throw new TestSequenceCompletedException();
                }
                return $commands[$currentCommand++];
            });

        $output
            ->expects($this->exactly(3))
            ->method('writeln')
            ->willReturnCallback(function($message) use (&$expectedOutput) {
                $expectedOutput[] = $message;
            });

        $app = Application::instance();
        $app->set(OutputContract::class, $output);
        $renderer = new ConsoleRendererFactory($app)->create();

        $this->executeWithExceptionCatch(function () use ($renderer) {
            $renderer->render(new RuntimeException('Test exception'));
        });
    }


    public function testRenderSource()
    {
        $expectedOutput = [];
        $output = $this->createStub(OutputContract::class);
        $commands = ['source 0'];
        $currentCommand = 0;
        $output->method('question')
            ->willReturnCallback(function () use ($commands, &$currentCommand) {
                if ($currentCommand >= count($commands)) {
                    throw new TestSequenceCompletedException();
                }
                return $commands[$currentCommand++];
            });
        $output->method('writeln')
            ->willReturnCallback(function($message) use (&$expectedOutput) {
                $expectedOutput[] = $message;
            });

        $app = Application::instance();
        $app->set(OutputContract::class, $output);
        $renderer = new ConsoleRendererFactory($app)->create();

        $this->executeWithExceptionCatch(function () use ($renderer) {
            $renderer->render(new RuntimeException('Test exception'));
        });

        $this->assertCount(44, $expectedOutput);
    }

    public function testRenderVars()
    {
        $expectedOutput = [];
        $output = $this->createStub(OutputContract::class);
        $commands = ['vars 0'];
        $currentCommand = 0;
        $output->method('question')
            ->willReturnCallback(function () use ($commands, &$currentCommand) {
                if ($currentCommand >= count($commands)) {
                    throw new TestSequenceCompletedException();
                }
                return $commands[$currentCommand++];
            });
        $output->method('writeln')
            ->willReturnCallback(function($message) use (&$expectedOutput) {
                $expectedOutput[] = $message;
            });

        $app = Application::instance();
        $app->set(OutputContract::class, $output);
        $renderer = new ConsoleRendererFactory($app)->create();

        $this->executeWithExceptionCatch(function () use ($renderer) {
            $renderer->render(new RuntimeException('Test exception'));
        });


        $this->assertTrue(in_array('$this = null', $expectedOutput));
    }

    public function testRenderEnv()
    {
        $expectedOutput = [];
        $output = $this->createStub(OutputContract::class);
        $commands = ['env'];
        $currentCommand = 0;
        $output->method('question')
            ->willReturnCallback(function () use ($commands, &$currentCommand) {
                if ($currentCommand >= count($commands)) {
                    throw new TestSequenceCompletedException();
                }
                return $commands[$currentCommand++];
            });
        $output->method('writeln')
            ->willReturnCallback(function($message) use (&$expectedOutput) {
                $expectedOutput[] = $message;
            });

        $app = Application::instance();
        $app->set(OutputContract::class, $output);
        $renderer = new ConsoleRendererFactory($app)->create();

        $this->executeWithExceptionCatch(function () use ($renderer) {
            $renderer->render(new RuntimeException('Test exception'));
        });


        $this->assertTrue(in_array('Server: CLI', $expectedOutput));
    }

    public function testRenderInvalidCommand()
    {
        $expectedOutput = [];
        $output = $this->createStub(OutputContract::class);
        $commands = ['missing'];
        $currentCommand = 0;
        $output->method('question')
            ->willReturnCallback(function () use ($commands, &$currentCommand) {
                if ($currentCommand >= count($commands)) {
                    throw new TestSequenceCompletedException();
                }
                return $commands[$currentCommand++];
            });
        $output->method('writeln')
            ->willReturnCallback(function($message) use (&$expectedOutput) {
                $expectedOutput[] = $message;
            });

        $app = Application::instance();
        $app->set(OutputContract::class, $output);
        $renderer = new ConsoleRendererFactory($app)->create();

        $this->executeWithExceptionCatch(function () use ($renderer) {
            $renderer->render(new RuntimeException('Test exception'));
        });

        $this->assertCount(3, $expectedOutput);
    }

    public function testRenderExit()
    {
        $commands = ['help', 'frame 0', 'exit'];
        $currentCommand = 0;

        $output = $this->createStub(OutputContract::class);
        $output->method('question')
            ->willReturnCallback(function() use ($commands, &$currentCommand) {
                return $commands[$currentCommand++];
            });

        $session = new DebugSession(
            $output,
            $this->createStub(ConsoleCommandProcessor::class),
            $this->createStub(TraceCollection::class)
        );

        // Act
        $session->run();

        // Assert
        $this->assertEquals(3, $currentCommand);
    }

    private function executeWithExceptionCatch(callable $callback): void
    {
        try {
            $callback();
        } catch (TestSequenceCompletedException) {
            return;
        }

        $this->fail('Test sequence not completed');
    }
}