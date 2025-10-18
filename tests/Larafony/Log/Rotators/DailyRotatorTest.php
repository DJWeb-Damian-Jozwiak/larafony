<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Log\Rotators;

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Log\Rotators\DailyRotator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DailyRotator::class)]
final class DailyRotatorTest extends TestCase
{
    private string $tempDir;
    private string $logPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/larafony_rotator_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        $this->logPath = $this->tempDir . '/app.log';
        ClockFactory::reset();
    }

    protected function tearDown(): void
    {
        ClockFactory::reset();
        // Cleanup temp files
        $files = glob($this->tempDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
        parent::tearDown();
    }

    #[Test]
    public function it_returns_false_if_file_does_not_exist(): void
    {
        $rotator = new DailyRotator();

        $this->assertFalse($rotator->shouldRotate($this->logPath));
    }

    #[Test]
    public function it_returns_false_if_file_is_from_today(): void
    {
        ClockFactory::freeze('2024-01-15 10:00:00');
        // Set file modification time to today
        touch($this->logPath, ClockFactory::timestamp());

        $rotator = new DailyRotator();

        $this->assertFalse($rotator->shouldRotate($this->logPath));
    }

    #[Test]
    public function it_returns_true_if_file_is_from_different_day(): void
    {
        // Create file with yesterday's timestamp
        touch($this->logPath, strtotime('-1 day'));
        ClockFactory::freeze('2024-01-15 10:00:00');

        $rotator = new DailyRotator();

        $this->assertTrue($rotator->shouldRotate($this->logPath));
    }

    #[Test]
    public function it_generates_rotated_filename_with_date(): void
    {
        ClockFactory::freeze('2024-01-15 10:00:00');
        $rotator = new DailyRotator();

        $rotatedPath = $rotator->rotate($this->logPath);

        $this->assertStringContainsString('app-2024-01-15.log', $rotatedPath);
    }

    #[Test]
    public function it_preserves_directory_path_in_rotation(): void
    {
        ClockFactory::freeze('2024-01-15 10:00:00');
        $rotator = new DailyRotator();

        $rotatedPath = $rotator->rotate($this->logPath);

        $this->assertStringStartsWith($this->tempDir, $rotatedPath);
    }

    #[Test]
    public function it_cleans_up_old_files(): void
    {
        ClockFactory::freeze('2024-01-15 10:00:00');
        $rotator = new DailyRotator(maxDays: 7);

        // Create files older than 7 days
        $oldFile1 = $this->tempDir . '/app-2024-01-01.log';
        $oldFile2 = $this->tempDir . '/app-2024-01-02.log';
        $recentFile = $this->tempDir . '/app-2024-01-14.log';

        $oldTimestamp = ClockFactory::now()->modify('-10 days')->getTimestamp();
        $old2Timestamp = ClockFactory::now()->modify('-9 days')->getTimestamp();
        $recentTimestamp = ClockFactory::now()->modify('-1 day')->getTimestamp();

        touch($oldFile1, $oldTimestamp);
        touch($oldFile2, $old2Timestamp);
        touch($recentFile, $recentTimestamp);

        $rotator->cleanup($this->logPath);

        $this->assertFileDoesNotExist($oldFile1);
        $this->assertFileDoesNotExist($oldFile2);
        $this->assertFileExists($recentFile);
    }

    #[Test]
    public function it_respects_custom_max_days(): void
    {
        ClockFactory::freeze('2024-01-15 10:00:00');
        $rotator = new DailyRotator(maxDays: 3);

        $oldFile = $this->tempDir . '/app-2024-01-10.log';
        $recentFile = $this->tempDir . '/app-2024-01-13.log';

        $oldTimestamp = ClockFactory::now()->modify('-5 days')->getTimestamp();
        $recentTimestamp = ClockFactory::now()->modify('-2 days')->getTimestamp();

        touch($oldFile, $oldTimestamp);
        touch($recentFile, $recentTimestamp);

        $rotator->cleanup($this->logPath);

        $this->assertFileDoesNotExist($oldFile);
        $this->assertFileExists($recentFile);
    }

    #[Test]
    public function it_only_deletes_files_matching_pattern(): void
    {
        ClockFactory::freeze('2024-01-15 10:00:00');
        $rotator = new DailyRotator(maxDays: 7);

        $logFile = $this->tempDir . '/app-2024-01-01.log';
        $otherFile = $this->tempDir . '/other-file.txt';

        $oldTimestamp = ClockFactory::now()->modify('-10 days')->getTimestamp();

        touch($logFile, $oldTimestamp);
        touch($otherFile, $oldTimestamp);

        $rotator->cleanup($this->logPath);

        $this->assertFileDoesNotExist($logFile);
        $this->assertFileExists($otherFile);
    }

    #[Test]
    public function it_uses_custom_pattern_if_provided(): void
    {
        ClockFactory::freeze('2024-01-15 10:00:00');
        $pattern = '/^custom\-\d{4}\-\d{2}\-\d{2}\.log$/';
        $rotator = new DailyRotator(maxDays: 7, pattern: $pattern);

        $matchingFile = $this->tempDir . '/custom-2024-01-01.log';
        $nonMatchingFile = $this->tempDir . '/app-2024-01-01.log';

        $oldTimestamp = ClockFactory::now()->modify('-10 days')->getTimestamp();

        touch($matchingFile, $oldTimestamp);
        touch($nonMatchingFile, $oldTimestamp);

        $rotator->cleanup($this->logPath);

        $this->assertFileDoesNotExist($matchingFile);
        $this->assertFileExists($nonMatchingFile);
    }

    #[Test]
    public function it_handles_files_with_different_extensions(): void
    {
        ClockFactory::freeze('2024-01-15 10:00:00');
        $logPath = $this->tempDir . '/app.txt';
        $rotator = new DailyRotator();

        $rotatedPath = $rotator->rotate($logPath);

        $this->assertStringEndsWith('app-2024-01-15.txt', $rotatedPath);
    }
}
