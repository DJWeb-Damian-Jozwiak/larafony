<?php

declare(strict_types=1);

namespace Larafony\Tests\Config\Environment;

use PHPUnit\Framework\TestCase;
use Larafony\Framework\Config\Environment\EnvironmentLoader;
use Larafony\Framework\Config\Environment\Exception\EnvironmentError;

class EnvironmentLoaderTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'env');

        // Wyczyść zmienne środowiskowe z poprzednich testów
        unset($_ENV['TEST_VAR']);
        unset($_SERVER['TEST_VAR']);
        unset($_ENV['EXISTING_VAR']);
        unset($_SERVER['EXISTING_VAR']);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testLoadsFileAndSetsEnvironmentVariables(): void
    {
        file_put_contents($this->tempFile, "TEST_VAR=test_value");

        $loader = new EnvironmentLoader();
        $result = $loader->load($this->tempFile);

        $this->assertEquals('test_value', $_ENV['TEST_VAR']);
        $this->assertEquals('test_value', $_SERVER['TEST_VAR']);
        $this->assertEquals('test_value', getenv('TEST_VAR'));
    }

    public function testThrowsExceptionWhenFileNotFound(): void
    {
        $this->expectException(EnvironmentError::class);
        $this->expectExceptionMessage('Environment file not found');

        $loader = new EnvironmentLoader();
        $loader->load('/nonexistent/.env');
    }

    public function testRespectsOverwriteSettingFalse(): void
    {
        $_ENV['EXISTING_VAR'] = 'original';
        $_SERVER['EXISTING_VAR'] = 'original';

        file_put_contents($this->tempFile, "EXISTING_VAR=new");

        // Bez nadpisywania
        $loader = new EnvironmentLoader();
        $loader->load($this->tempFile);

        $this->assertEquals('original', $_ENV['EXISTING_VAR']);
    }

    public function testParseContentDoesNotSetEnvironmentVariables(): void
    {
        $content = "PARSE_ONLY=value";

        $loader = new EnvironmentLoader();
        $result = $loader->parseContent($content);

        $this->assertEquals('value', $result->get('PARSE_ONLY'));
        $this->assertArrayNotHasKey('PARSE_ONLY', $_ENV);
    }

    public function testLoadsMultipleVariables(): void
    {
        $content = <<<ENV
        VAR1=value1
        VAR2=value2
        VAR3=value3
        ENV;

        file_put_contents($this->tempFile, $content);

        $loader = new EnvironmentLoader();
        $result = $loader->load($this->tempFile);

        $this->assertEquals('value1', $_ENV['VAR1']);
        $this->assertEquals('value2', $_ENV['VAR2']);
        $this->assertEquals('value3', $_ENV['VAR3']);
        $this->assertEquals(3, $result->count());
    }

    public function testThrowsExceptionForUnreadableFile(): void
    {
        file_put_contents($this->tempFile, "TEST=value");
        chmod($this->tempFile, 0000);

        $this->expectException(\RuntimeException::class);
        try {
            $loader = new EnvironmentLoader();
            $loader->load($this->tempFile);
        } finally {
            chmod($this->tempFile, 0644);
        }
    }
}
