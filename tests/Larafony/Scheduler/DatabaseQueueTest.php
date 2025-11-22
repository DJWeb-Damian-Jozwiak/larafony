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

class DatabaseQueueTest extends TestCase
{
    private TestJob $job;
    private DatabaseManager $dbManager;
    private QueryBuilder $queryBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->job = new TestJob(
            id: '123',
            title: 'Test Job'
        );

        // Set a fixed test time
        ClockFactory::freeze(new DateTimeImmutable('2024-01-01 12:00:00'));

        // Setup DB facade with mocks
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->dbManager = $this->createMock(DatabaseManager::class);
        $this->dbManager->method('table')->willReturn($this->queryBuilder);
        Application::instance()->set(DatabaseManager::class, $this->dbManager);
        DB::withManager($this->dbManager);
    }

    protected function tearDown(): void
    {
        ClockFactory::reset();
        parent::tearDown();
    }

    public function testPush(): void
    {
        // Mock successful insert
        $this->queryBuilder->expects($this->once())
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
        $delay = new DateTime('2024-01-01 14:00:00');

        // Mock successful insert with delayed time
        $this->queryBuilder->expects($this->once())
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
        // Mock finding the job
        $this->queryBuilder->expects($this->any())
            ->method('where')
            ->with('id', '=', '123')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
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
        $this->queryBuilder->expects($this->once())
            ->method('delete')
            ->willReturn(1);

        $queue = new DatabaseQueue();
        $queue->delete('123');

        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function testSize(): void
    {
        // Mock count query
        $this->queryBuilder->expects($this->exactly(2))
            ->method('where')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('count')
            ->willReturn(3);

        $queue = new DatabaseQueue();
        $size = $queue->size();

        $this->assertEquals(3, $size);
    }

    public function testPop(): void
    {
        // Mock finding and deleting job
        $this->queryBuilder->expects($this->any())
            ->method('where')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('available_at', OrderDirection::ASC)
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
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

        $this->queryBuilder->expects($this->once())
            ->method('delete')
            ->willReturn(1);

        $queue = new DatabaseQueue();
        $job = $queue->pop();

        $this->assertInstanceOf(JobContract::class, $job);
        $this->assertInstanceOf(TestJob::class, $job);
    }

    public function testPopEmptyQueue(): void
    {
        // Mock empty result
        $this->queryBuilder->expects($this->exactly(2))
            ->method('where')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('available_at', OrderDirection::ASC)
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('first')
            ->willReturn(null);

        $queue = new DatabaseQueue();
        $job = $queue->pop();

        $this->assertNull($job);
    }

    public function testPopRespectsAvailableAt(): void
    {
        // Mock query that filters by available_at
        $this->queryBuilder->expects($this->exactly(2))
            ->method('where')
            ->willReturnCallback(function($column, $operator, $value) {
                if ($column === 'available_at' && $operator === '<=') {
                    $this->assertInstanceOf(DateTimeImmutable::class, $value);
                }
                return $this->queryBuilder;
            });

        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('first')
            ->willReturn(null); // No jobs available yet

        $queue = new DatabaseQueue();
        $job = $queue->pop();

        $this->assertNull($job);
    }
}
