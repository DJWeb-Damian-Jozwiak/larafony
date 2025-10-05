<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Http\Factories;

use InvalidArgumentException;
use Larafony\Framework\Http\Factories\StreamFactory;
use Larafony\Framework\Http\Factories\UploadedFileFactory;
use Larafony\Framework\Http\Stream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

class UploadedFileFactoryTest extends TestCase
{
    private string $tempDir;
    private string $testFile;
    private string $targetPath;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'upload_test_' . uniqid();
        mkdir($this->tempDir);

        $this->testFile = $this->tempDir . DIRECTORY_SEPARATOR . 'test.txt';
        file_put_contents($this->testFile, 'Test content');
        $this->targetPath = $this->tempDir . DIRECTORY_SEPARATOR . 'moved_file.txt';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->targetPath)) {
            unlink($this->targetPath);
        }
        if (file_exists($this->testFile)) {
            unlink($this->testFile);
        }
        if (is_dir($this->tempDir)) {
            @rmdir($this->tempDir);
        }
    }

    public function testCreateUploadedFileWithValidStream(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getSize')->willReturn(123);

        $file = new UploadedFileFactory()->createUploadedFile(
            $stream,
            123,
            UPLOAD_ERR_OK,
            'test.txt',
            'text/plain'
        );

        $this->assertInstanceOf(UploadedFileInterface::class, $file);
        $this->assertEquals(123, $file->getSize());
        $this->assertEquals(UPLOAD_ERR_OK, $file->getError());
        $this->assertEquals('test.txt', $file->getClientFilename());
        $this->assertEquals('text/plain', $file->getClientMediaType());
    }

    public function testCreateUploadedFileWithValidFilePath(): void
    {
        $file = UploadedFileFactory::create(
            $this->testFile,
            filesize($this->testFile),
            UPLOAD_ERR_OK,
            'test.txt',
            'text/plain'
        );

        $this->assertInstanceOf(UploadedFileInterface::class, $file);
        $this->assertEquals(filesize($this->testFile), $file->getSize());
        $this->assertEquals(UPLOAD_ERR_OK, $file->getError());
        $this->assertInstanceOf(StreamInterface::class, $file->getStream());
    }

    public function testMoveToWithValidPath(): void
    {
        $file = UploadedFileFactory::create(
            $this->testFile,
            filesize($this->testFile),
            UPLOAD_ERR_OK
        );

        $file->moveTo($this->targetPath);

        $this->assertFileExists($this->targetPath);
        $this->assertEquals('Test content', file_get_contents($this->targetPath));
    }

    public function testMoveStream(): void
    {
        $stream = new StreamFactory()->createStream();
        $stream->write('Test content');
        $file = new UploadedFileFactory()->createUploadedFile(
            $stream,
            $stream->getSize(),
            UPLOAD_ERR_OK
        );

        $file->moveTo($this->targetPath);

        $this->assertFileExists($this->targetPath);
    }

    public function testMoveToThrowsWhenCalledTwice(): void
    {
        $file = UploadedFileFactory::create(
            $this->testFile,
            filesize($this->testFile),
            UPLOAD_ERR_OK
        );

        $file->moveTo($this->targetPath);

        $this->expectException(RuntimeException::class);
        $file->moveTo($this->targetPath . '_second');
    }

    public function testMoveToThrowsWithInvalidTarget(): void
    {
        $file = UploadedFileFactory::create(
            $this->testFile,
            filesize($this->testFile),
            UPLOAD_ERR_OK
        );
        $this->expectException(InvalidArgumentException::class);
        $file->moveTo('');
    }

    public function testMoveToThrowsWithNonexistentDirectory(): void
    {
        $file = UploadedFileFactory::create(
            $this->testFile,
            filesize($this->testFile),
            UPLOAD_ERR_OK
        );

        $this->expectException(RuntimeException::class);
        $file->moveTo('/nonexistent/directory/file.txt');
    }

    public function testGetStreamWithValidFile(): void
    {
        $stream = new StreamFactory()->createStream();
        $stream->write('Test content');
        $file = new UploadedFileFactory()->createUploadedFile(
            $stream,
            $stream->getSize(),
            UPLOAD_ERR_OK
        );

        $stream = $file->getStream();
        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertEquals('Test content', $stream->getContents());
    }
    #[DataProvider('uploadErrorProvider')]
    public function testUploadErrors(int $errorCode, string $expectedException): void
    {
        $file = UploadedFileFactory::create(
            $this->testFile,
            filesize($this->testFile),
            $errorCode
        );

        $this->expectException($expectedException);
        $file->moveTo($this->targetPath);
    }

    public static function uploadErrorProvider(): array
    {
        return [
            'UPLOAD_ERR_INI_SIZE' => [UPLOAD_ERR_INI_SIZE, RuntimeException::class],
            'UPLOAD_ERR_FORM_SIZE' => [UPLOAD_ERR_FORM_SIZE, RuntimeException::class],
            'UPLOAD_ERR_PARTIAL' => [UPLOAD_ERR_PARTIAL, RuntimeException::class],
            'UPLOAD_ERR_NO_FILE' => [UPLOAD_ERR_NO_FILE, RuntimeException::class],
            'UPLOAD_ERR_NO_TMP_DIR' => [UPLOAD_ERR_NO_TMP_DIR, RuntimeException::class],
            'UPLOAD_ERR_CANT_WRITE' => [UPLOAD_ERR_CANT_WRITE, RuntimeException::class],
            'UPLOAD_ERR_EXTENSION' => [UPLOAD_ERR_EXTENSION, RuntimeException::class],
        ];
    }

    public function testGetStreamWithUploadError(): void
    {
        $file = UploadedFileFactory::create(
            $this->testFile,
            filesize($this->testFile),
            UPLOAD_ERR_NO_FILE
        );

        $this->expectException(RuntimeException::class);
        $file->getStream();
    }

    public function testWithNonWritableTargetDirectory(): void
    {
        $file = UploadedFileFactory::create(
            $this->testFile,
            filesize($this->testFile),
            UPLOAD_ERR_OK
        );

        chmod($this->tempDir, 0444);

        $this->expectException(RuntimeException::class);
        $file->moveTo($this->tempDir . '/new_file.txt');
    }
}