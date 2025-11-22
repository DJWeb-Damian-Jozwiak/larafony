<?php

namespace Larafony\Framework\Tests\Scheduler;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Larafony\Framework\Tests\Helpers\TestJob;

class TestJobTest extends TestCase
{
    public function testSerialization(): void
    {
        $job = new TestJob(
            id: '123',
            title: 'Test Title',
            name: 'Jane Doe',
            email: 'jane@example.com'
        );

        $serialized = serialize($job);
        /** @var TestJob $unserialized */
        $unserialized = unserialize($serialized);

        $this->assertSame('Jane Doe', $unserialized->name);
        $this->assertSame('jane@example.com', $unserialized->email);
        $this->assertSame(30, $unserialized->age);
        $this->assertSame('123', $unserialized->id);
        $this->assertSame('Test Title', $unserialized->title);
    }

    public function testCustomSerializeNames(): void
    {
        $job = new TestJob(
            id: '123',
            title: 'Test Title'
        );

        $serializedData = $job->__serialize();

        $this->assertArrayHasKey('custom_email', $serializedData);
        $this->assertArrayHasKey('custom_title', $serializedData);
        $this->assertSame('john@example.com', $serializedData['custom_email']);
        $this->assertSame('Test Title', $serializedData['custom_title']);
    }

    public function testUnserialize(): void
    {
        $data = [
            'name' => 'Bob Smith',
            'custom_email' => 'bob@example.com',
            'age' => 25,
            'id' => '456',
            'custom_title' => 'New Title'
        ];

        $job = new TestJob(
            id: '',
            title: ''
        );

        $job->__unserialize($data);

        $this->assertSame('Bob Smith', $job->name);
        $this->assertSame('bob@example.com', $job->email);
        $this->assertSame(25, $job->age);
        $this->assertSame('456', $job->id);
        $this->assertSame('New Title', $job->title);
    }

    public function testProtectedSetVisibility(): void
    {
        $job = new TestJob(
            id: '123',
            title: 'Test Title'
        );

        $this->assertSame('123', $job->id);
        $this->assertSame('Test Title', $job->title);
    }
}
