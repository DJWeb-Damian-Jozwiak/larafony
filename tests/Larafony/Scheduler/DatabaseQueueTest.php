<?php

namespace Larafony\Framework\Tests\Scheduler;

use DateTime;
use DateTimeImmutable;
use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Database\Base\Query\Contracts\QueryBuilderContract;
use Larafony\Framework\Database\Base\Query\Enums\OrderDirection;
use Larafony\Framework\Database\Base\Query\QueryBuilder;
use Larafony\Framework\Database\DatabaseManager;
use Larafony\Framework\Database\ORM\DB;
use Larafony\Framework\Database\ORM\Entities\Job as JobEntity;
use Larafony\Framework\Scheduler\Contracts\JobContract;
use Larafony\Framework\Scheduler\Queue\DatabaseQueue;
use Larafony\Framework\Tests\TestCase;
use Larafony\Framework\Tests\Helpers\TestJob;
use Larafony\Framework\Web\Application;
use PHPUnit\Framework\MockObject\MockObject;

class DatabaseQueueTest extends TestCase
{
    private TestJob $job;

    protected function setUp(): void
    {
        parent::setUp();

        $this->job = new TestJob(
            id: '123',
            title: 'Test Job'
        );

        // Set a fixed test time
        ClockFactory::freeze(new DateTimeImmutable('2024-01-01 12:00:00'));
    }

    protected function tearDown(): void
    {
        ClockFactory::reset();
        parent::tearDown();
    }

    private function setUpDbWithMock(): QueryBuilder&MockObject
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $dbManager = $this->createStub(DatabaseManager::class);
        $dbManager->method('table')->willReturn($queryBuilder);
        Application::instance()->set(DatabaseManager::class, $dbManager);
        DB::withManager($dbManager);
        return $queryBuilder;
    }

    public function testPush(): void
    {
        $queryBuilder = $this->setUpDbWithMock();

        // Mock successful insert
        $queryBuilder->expects($this->once())
            ->method('insertGetId')
            ->with($this->callback(function($data) {
                return isset($data['payload'])
                    && isset($data['queue'])
                    && isset($data['attempts'])
                    && isset($data['available_at'])
                    && isset($data['created_at']);
            }));

        $queue = new DatabaseQueue();
        $jobId = $queue->push($this->job);

        $this->assertNotEmpty($jobId);
    }

    public function testLater(): void
    {
        $queryBuilder = $this->setUpDbWithMock();
        $delay = new DateTime('2024-01-01 14:00:00');

        // Mock successful insert with delayed time
        $queryBuilder->expects($this->once())
            ->method('insertGetId')
            ->with($this->callback(function($data) use ($delay) {
                return isset($data['payload'])
                    && isset($data['available_at'])
                    && $data['available_at'] === $delay->format('Y-m-d H:i:s');
            }));

        $queue = new DatabaseQueue();
        $jobId = $queue->later($delay, $this->job);

        $this->assertNotEmpty($jobId);
    }

    public function testDelete(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        // Mock finding the job
        $queryBuilder->method('where')
            ->with('id', '=', '123')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('first')
            ->willReturn([
                'id' => 123,
                'payload' => serialize($this->job),
                'queue' => 'default',
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => '2024-01-01 12:00:00',
                'created_at' => '2024-01-01 12:00:00',
            ]);

        // Mock delete
        $queryBuilder->expects($this->once())
            ->method('delete')
            ->willReturn(1);

        $dbManager = $this->createStub(DatabaseManager::class);
        $dbManager->method('table')->willReturn($queryBuilder);
        DB::withManager($dbManager);

        $queue = new DatabaseQueue();
        $queue->delete('123');

        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function testSize(): void
    {
        $queryBuilder = $this->setUpDbWithMock();

        // Mock count query
        $queryBuilder->expects($this->exactly(2))
            ->method('where')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('count')
            ->willReturn(3);

        $queue = new DatabaseQueue();
        $size = $queue->size();

        $this->assertEquals(3, $size);
    }

    public function testPop(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        // Mock finding and deleting job
        $queryBuilder->method('where')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('available_at', OrderDirection::ASC)
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('first')
            ->willReturn([
                'id' => 123,
                'payload' => serialize($this->job),
                'queue' => 'default',
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => '2024-01-01 12:00:00',
                'created_at' => '2024-01-01 12:00:00',
            ]);

        $queryBuilder->expects($this->once())
            ->method('delete')
            ->willReturn(1);

        $dbManager = $this->createStub(DatabaseManager::class);
        $dbManager->method('table')->willReturn($queryBuilder);
        DB::withManager($dbManager);

        $queue = new DatabaseQueue();
        $job = $queue->pop();

        $this->assertInstanceOf(JobContract::class, $job);
        $this->assertInstanceOf(TestJob::class, $job);
    }

    public function testPopEmptyQueue(): void
    {
        $queryBuilder = $this->setUpDbWithMock();

        // Mock empty result
        $queryBuilder->expects($this->exactly(2))
            ->method('where')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('available_at', OrderDirection::ASC)
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('first')
            ->willReturn(null);

        $queue = new DatabaseQueue();
        $job = $queue->pop();

        $this->assertNull($job);
    }

    public function testPopRespectsAvailableAt(): void
    {
        $queryBuilder = $this->setUpDbWithMock();

        // Mock query that filters by available_at
        $queryBuilder->expects($this->exactly(2))
            ->method('where')
            ->willReturnCallback(function($column, $operator, $value) use ($queryBuilder) {
                if ($column === 'available_at' && $operator === '<=') {
                    $this->assertInstanceOf(DateTimeImmutable::class, $value);
                }
                return $queryBuilder;
            });

        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('first')
            ->willReturn(null); // No jobs available yet

        $queue = new DatabaseQueue();
        $job = $queue->pop();

        $this->assertNull($job);
    }
}
