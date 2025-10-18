<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Log;

use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Exceptions\Log\LoggerError;
use Larafony\Framework\Log\Logger;
use Larafony\Framework\Log\LoggerFactory;
use Larafony\Framework\Web\Application;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(LoggerFactory::class)]
final class LoggerFactoryTest extends TestCase
{
    private string $tempDir;
    private ConfigContract $configMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/larafony_logger_factory_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);

        // Create mock config and bind it to Application
        $this->configMock = $this->createMock(ConfigContract::class);
        Application::instance(base_path: '/tmp/test')->set(ConfigContract::class, $this->configMock);
    }

    protected function tearDown(): void
    {
        $files = glob($this->tempDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
        Application::empty();
        parent::tearDown();
    }

    private function setLoggingConfig(array $config): void
    {
        $this->configMock
            ->method('get')
            ->with('logging')
            ->willReturn($config);
    }

    #[Test]
    public function it_creates_logger_with_file_handler_and_text_formatter(): void
    {
        $this->setLoggingConfig([
            'channels' => [
                [
                    'handler' => 'file',
                    'path' => $this->tempDir . '/app.log',
                    'formatter' => 'text',
                    'max_days' => 7,
                ],
            ],
        ]);

        $logger = LoggerFactory::create();

        $this->assertInstanceOf(LoggerInterface::class, $logger);
        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertCount(1, $logger->handlers);
    }

    #[Test]
    public function it_creates_logger_with_file_handler_and_json_formatter(): void
    {
        $this->setLoggingConfig( [
            'channels' => [
                [
                    'handler' => 'file',
                    'path' => $this->tempDir . '/app.log',
                    'formatter' => 'json',
                    'max_days' => 14,
                ],
            ],
        ]);

        $logger = LoggerFactory::create();

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertCount(1, $logger->handlers);
    }

    #[Test]
    public function it_creates_logger_with_file_handler_and_xml_formatter(): void
    {
        $this->setLoggingConfig( [
            'channels' => [
                [
                    'handler' => 'file',
                    'path' => $this->tempDir . '/app.log',
                    'formatter' => 'xml',
                    'max_days' => 30,
                ],
            ],
        ]);

        $logger = LoggerFactory::create();

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertCount(1, $logger->handlers);
    }

    #[Test]
    public function it_defaults_to_text_formatter_for_unknown_formatter(): void
    {
        $this->setLoggingConfig( [
            'channels' => [
                [
                    'handler' => 'file',
                    'path' => $this->tempDir . '/app.log',
                    'formatter' => 'unknown',
                    'max_days' => 7,
                ],
            ],
        ]);

        $logger = LoggerFactory::create();

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertCount(1, $logger->handlers);
    }

    #[Test]
    public function it_creates_logger_with_database_handler(): void
    {
        $this->setLoggingConfig( [
            'channels' => [
                [
                    'handler' => 'database',
                ],
            ],
        ]);

        $logger = LoggerFactory::create();

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertCount(1, $logger->handlers);
    }

    #[Test]
    public function it_creates_logger_with_multiple_handlers(): void
    {
        $this->setLoggingConfig( [
            'channels' => [
                [
                    'handler' => 'file',
                    'path' => $this->tempDir . '/app.log',
                    'formatter' => 'text',
                    'max_days' => 7,
                ],
                [
                    'handler' => 'database',
                ],
                [
                    'handler' => 'file',
                    'path' => $this->tempDir . '/errors.log',
                    'formatter' => 'json',
                    'max_days' => 14,
                ],
            ],
        ]);

        $logger = LoggerFactory::create();

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertCount(3, $logger->handlers);
    }

    #[Test]
    public function it_uses_default_max_days_if_not_provided(): void
    {
        $this->setLoggingConfig( [
            'channels' => [
                [
                    'handler' => 'file',
                    'path' => $this->tempDir . '/app.log',
                    'formatter' => 'text',
                ],
            ],
        ]);

        $logger = LoggerFactory::create();

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertCount(1, $logger->handlers);
    }

    #[Test]
    public function it_throws_exception_for_unknown_handler(): void
    {
        $this->setLoggingConfig( [
            'channels' => [
                [
                    'handler' => 'unknown_handler',
                ],
            ],
        ]);

        $this->expectException(LoggerError::class);
        $this->expectExceptionMessage('Unknown handler type: unknown_handler');

        LoggerFactory::create();
    }

    #[Test]
    public function it_creates_logger_with_empty_channels(): void
    {
        $this->setLoggingConfig( [
            'channels' => [],
        ]);

        $logger = LoggerFactory::create();

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertCount(0, $logger->handlers);
    }
}
