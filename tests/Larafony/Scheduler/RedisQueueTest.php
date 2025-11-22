<?php

namespace Larafony\Framework\Tests\Scheduler;

use DateTime;
use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Encryption\KeyGenerator;
use Larafony\Framework\Scheduler\Queue\RedisQueue;
use Larafony\Framework\Scheduler\QueueFactory;
use Larafony\Framework\Web\Application;
use Redis;
use Larafony\Framework\Tests\TestCase;
use Larafony\Framework\Tests\Helpers\TestJob;

class RedisQueueTest extends TestCase
{
    private Redis $redis;
    private RedisQueue $queue;
    private TestJob $job;
    private Application $app;

    protected function setUp(): void
    {
        parent::setUp();
        Application::empty(null);
        $this->app = Application::instance();
        $this->job = new TestJob(
            id: '123',
            title: 'Test Job'
        );
        $this->app->bind('base_path', dirname(__DIR__));
        $security = new KeyGenerator()->generateKey();
        $config = $this->createMock(ConfigContract::class);
        $config->expects($this->any())->method('get')->willReturnCallback(fn($key) => match ($key) {
            'app.key' => $security,
            'redis.host' => 'localhost',
            'redis.port' => 6379,
            'queue.default' => 'redis',
            default => null,
        });
        $this->app->set(ConfigContract::class, $config);
        $this->redis = $this->createMock(Redis::class);
        $this->redis->expects($this->any())->method('connect')->willReturn(true);
        $this->queue = QueueFactory::make()->withRedis($this->redis);
        $this->job = new TestJob(
            id: '123',
            title: 'Test Job'
        );
    }

    public function testPush(): void
    {
        $this->redis->expects($this->once())
            ->method('zAdd')
            ->with(
                $this->equalTo('queue:delayed'),
                $this->equalTo(time()),
                $this->callback(function($value) {
                    $data = json_decode($value, true);
                    return !empty($data['id']) && !empty($data['job']);
                })
            )
            ->willReturn(1);

        $jobId = $this->queue->push($this->job);
        $this->assertNotEmpty($jobId);
    }

    public function testLater(): void
    {
        $delay = new DateTime('2024-01-01 12:00:00');

        $this->redis->expects($this->once())
            ->method('zAdd')
            ->with(
                $this->equalTo('queue:delayed'),
                $this->equalTo($delay->getTimestamp()),
                $this->callback(function($value) {
                    $data = json_decode($value, true);
                    return !empty($data['id']) && !empty($data['job']);
                })
            )
            ->willReturn(1);

        $jobId = $this->queue->later($delay, $this->job);
        $this->assertNotEmpty($jobId);
    }

    public function testDelete(): void
    {
        $currentTime = time();

        $this->redis->expects($this->once())
            ->method('zRemRangeByScore')
            ->with(
                $this->equalTo('queue:delayed'),
                $this->equalTo('0'),
                $this->equalTo($currentTime . '')
            )
            ->willReturn(1);

        $this->queue->delete('123');
    }

    public function testSize(): void
    {
        $currentTime = time();

        $this->redis->expects($this->once())
            ->method('zCount')
            ->with(
                $this->equalTo('queue:delayed'),
                $this->equalTo('0'),
                $this->equalTo($currentTime . '')
            )
            ->willReturn(5);

        $size = $this->queue->size();
        $this->assertEquals(5, $size);
    }

    public function testPop(): void
    {
        $currentTime = time();
        $jobData = [
            'id' => 'test-id',
            'job' => serialize($this->job)
        ];
        $jobJson = json_encode($jobData);

        $this->redis->expects($this->once())
            ->method('zRangeByScore')
            ->with(
                $this->equalTo('queue:delayed'),
                $this->equalTo('0'),
                $this->equalTo($currentTime . ''),
                $this->equalTo(['limit' => [0, 1]])
            )
            ->willReturn([$jobJson]);

        $this->redis->expects($this->once())
            ->method('zRem')
            ->with(
                $this->equalTo('queue:delayed'),
                $this->equalTo($jobJson)
            )
            ->willReturn(1);

        $result = $this->queue->pop();
        $this->assertInstanceOf(TestJob::class, $result);
    }

    public function testPopEmptyQueue(): void
    {
        $currentTime = time();

        $this->redis->expects($this->once())
            ->method('zRangeByScore')
            ->with(
                $this->equalTo('queue:delayed'),
                $this->equalTo('0'),
                $this->equalTo($currentTime . ''),
                $this->equalTo(['limit' => [0, 1]])
            )
            ->willReturn([]);

        $result = $this->queue->pop();
        $this->assertNull($result);
    }
}
