<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Log;

use DateTimeImmutable;
use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Log\Metadata;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Metadata::class)]
final class MetadataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ClockFactory::reset();
    }

    protected function tearDown(): void
    {
        ClockFactory::reset();
        parent::tearDown();
    }

    #[Test]
    public function it_can_be_created_with_timestamp(): void
    {
        $timestamp = new DateTimeImmutable('2024-01-15 10:30:45');
        $metadata = new Metadata($timestamp);

        $array = $metadata->toArray();

        $this->assertArrayHasKey('timestamp', $array);
        $this->assertSame('2024-01-15 10:30:45', $array['timestamp']);
    }

    #[Test]
    public function it_can_be_created_with_factory_method(): void
    {
        ClockFactory::freeze('2024-01-15 10:30:45');

        $metadata = Metadata::create();
        $array = $metadata->toArray();

        $this->assertArrayHasKey('timestamp', $array);
        $this->assertSame('2024-01-15 10:30:45', $array['timestamp']);
    }

    #[Test]
    public function it_uses_clock_factory_for_current_time(): void
    {
        $frozenTime = '2024-12-25 15:30:00';
        ClockFactory::freeze($frozenTime);

        $metadata = Metadata::create();

        $this->assertSame($frozenTime, $metadata->toArray()['timestamp']);
    }

    #[Test]
    public function it_converts_to_array_with_formatted_timestamp(): void
    {
        $timestamp = new DateTimeImmutable('2024-01-01 00:00:00');
        $metadata = new Metadata($timestamp);

        $array = $metadata->toArray();

        $this->assertIsArray($array);
        $this->assertCount(1, $array);
        $this->assertSame('2024-01-01 00:00:00', $array['timestamp']);
    }
}
