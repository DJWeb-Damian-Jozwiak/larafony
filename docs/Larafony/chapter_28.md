# Chapter 28: Scheduler & Queue System

> Part of the Larafony Framework - A modern PHP 8.5 framework built from scratch
>
> 📚 Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)

## Overview

This chapter implements a complete job scheduling and queue processing system for Larafony, bringing enterprise-grade asynchronous task management to the framework. The scheduler system combines two powerful capabilities: a flexible queue system for background job processing and a cron-based task scheduler for recurring jobs.

The implementation follows Larafony's core principles: PSR compliance, attribute-based configuration, and minimal dependencies. Unlike Laravel's approach which relies heavily on configuration files, Larafony's scheduler uses PHP 8.5 attributes for job serialization and enum-based cron presets for scheduling. The system integrates seamlessly with the existing ORM for persistence and the Clock system for testable time operations.

The queue system supports multiple backends (database and Redis) and includes comprehensive failed job handling with retry mechanisms, making it production-ready for high-throughput applications. All console commands follow the established attribute-based command pattern, providing a consistent developer experience across the framework.

## Key Components

### Core Scheduler Components

- **Schedule** - Main scheduler class managing scheduled events and configuration-based task registration (uses ScheduledEvent wrapper, CronExpression parser)
- **CronExpression** - Parser and evaluator for standard cron expressions with support for ranges, frequencies, and wildcards
- **CronSchedule** - Enum providing 17 preset schedules (EVERY_MINUTE, DAILY, WEEKLY, etc.) with fluent `at()` method for time specification
- **Dispatcher** - Convenience facade for job dispatching with immediate, delayed, and batch dispatch capabilities

### Queue Infrastructure

- **QueueFactory** - Factory creating queue instances based on configuration (supports database and Redis drivers)
- **QueueWorker** - Worker processing jobs from queue with configurable iterations and automatic failed job logging (uses helper FailedJobRepository)
- **DatabaseQueue** - Database-backed queue implementation using ORM entities (Job entity with UUID support and Clock-based timestamps)
- **RedisQueue** - Redis-backed queue implementation using lists for high-performance queuing

### Job System

- **Job** (abstract) - Base class for all jobs with automatic serialization based on `#[Serialize]` attributes
- **JobContract** - Interface defining job execution contract with `handle()` and `handleException()` methods
- **FailedJobRepository** - ORM-based repository managing failed jobs with retry, prune, and recovery capabilities (uses FailedJob entity)

### Console Commands

- **QueueWork** - Process jobs from queue with options for iterations, queue selection, and stop conditions
- **ScheduleRun** - Run scheduled tasks (designed to be called every minute via cron)
- **QueueFailed**, **QueueRetry**, **QueueForget**, **QueueFlush**, **QueuePrune** - Failed job management commands
- **JobsTable**, **FailedJobsTable** - Migration stub generators for queue infrastructure

## PSR Standards Implemented

The Scheduler system adheres to established PSR patterns used throughout Larafony:

- **PSR-11**: Container integration via `ContainerContract` for dependency injection in commands and factories
- **PSR-3 Alignment**: Job exception handling follows PSR-3 logging principles through `handleException()` method
- **PSR-4**: Autoloading with `Larafony\Framework\Scheduler` namespace structure

The implementation uses interfaces (`JobContract`, `QueueContract`, `ScheduleContract`) to ensure loose coupling and testability, following SOLID principles and enabling easy extension with custom queue drivers or job types.

## New Attributes

### #[Serialize]

**Location:** `src/Larafony/Scheduler/Attributes/Serialize.php`

Marks constructor parameters or public properties for automatic serialization when jobs are queued. Supports optional custom serialization names.

```php
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Serialize
{
    public function __construct(public ?string $name = null) {}
}
```

**Usage:**
```php
class SendEmailJob extends Job
{
    public function __construct(
        #[Serialize] private string $to,
        #[Serialize] private string $subject,
        #[Serialize(name: 'email_body')] private string $body
    ) {}
}
```

The `Job` base class uses reflection to discover all `#[Serialize]` attributes and automatically handles serialization/unserialization through `__serialize()` and `__unserialize()` magic methods. This attribute-based approach is more explicit and type-safe than Laravel's implicit serialization of all constructor properties.

## Implementation Details

### DatabaseQueue - ORM Integration

**Location:** `src/Larafony/Scheduler/Queue/DatabaseQueue.php`

The DatabaseQueue implementation fully leverages Larafony's ORM and Clock systems:

```php
public function push(JobContract $job): string
{
    $jobEntity = new JobEntity();
    $jobEntity->payload = serialize($job);
    $jobEntity->queue = 'default';
    $jobEntity->attempts = 0;
    $jobEntity->reserved_at = null;
    $jobEntity->available_at = ClockFactory::instance(); // Clock object, not DateTimeImmutable
    $jobEntity->created_at = ClockFactory::instance();
    $jobEntity->save();

    return (string) $jobEntity->id; // Returns UUID when use_uuid = true
}
```

**Key Features:**
- **ORM-based**: Uses `JobEntity` model instead of raw SQL queries
- **Clock Integration**: Stores `Clock` objects directly in `available_at` and `created_at` fields
- **UUID Support**: Job entity has `use_uuid = true`, generating UUIDs instead of auto-increment IDs
- **Type Safety**: Uses `OrderDirection` enum for sorting instead of string literals
- **Delayed Jobs**: `later()` method creates `FrozenClock` instance for delayed execution

**Querying Available Jobs:**

```php
public function pop(): ?JobContract
{
    $jobEntity = JobEntity::query()
        ->where('available_at', '<=', ClockFactory::now())
        ->where('reserved_at', '=', null)
        ->orderBy('available_at', OrderDirection::ASC)
        ->first();

    if ($jobEntity === null) {
        return null;
    }

    $jobEntity->delete();
    return unserialize($jobEntity->payload);
}
```

This approach compares `Clock` objects directly in queries, leveraging the ORM's type casting system.

### Job Entity - Property Hooks with Clock

**Location:** `src/Larafony/Database/ORM/Entities/Job.php`

The Job entity uses PHP 8.5 property hooks with explicit backing properties to avoid circular references:

```php
class Job extends Model
{
    public protected(set) bool $use_uuid = true; // Enable UUID primary keys

    public Clock $available_at {
        get => $this->available_at;
        set {
            $this->available_at = $value;
            $this->markPropertyAsChanged('available_at');
        }
    }

    public Clock $created_at {
        get => $this->created_at;
        set {
            $this->created_at = $value;
            $this->markPropertyAsChanged('created_at');
        }
    }

    public array $casts = [
        'attempts' => 'int',
        'reserved_at' => 'datetime',
        'available_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}
```

**Design Decisions:**
1. **Clock Type**: Uses `Clock` interface instead of `DateTimeImmutable` for better testability
2. **UUID Primary Keys**: Enables `use_uuid` for distributed system compatibility
3. **Property Hooks**: Modern PHP 8.5 syntax with explicit `markPropertyAsChanged()` calls
4. **Type Casting**: ORM automatically converts between `Clock` objects and database datetime strings

### Failed Job Repository - UUID-Based Recovery

**Location:** `src/Larafony/Scheduler/FailedJobRepository.php`

Failed jobs use UUID identifiers for reliable tracking across distributed systems:

```php
public function log(
    string $connection,
    string $queue,
    JobContract $job,
    \Throwable $exception
): void {
    $failedJob = new FailedJob();
    $failedJob->uuid = Str::uuid(); // RFC 4122 v4 UUID
    $failedJob->connection = $connection;
    $failedJob->queue = $queue;
    $failedJob->payload = serialize($job);
    $failedJob->exception = $this->formatException($exception);
    $failedJob->failed_at = ClockFactory::now();
    $failedJob->save();
}
```

The UUID approach ensures:
- **Uniqueness**: No collisions across multiple queue workers
- **Portability**: IDs remain unique when migrating between databases
- **Security**: Non-sequential IDs prevent enumeration attacks

## Database Setup

### Creating Queue Tables

Before using the queue system, you need to create the required database tables:

```bash
# Generate jobs table migration
php bin/larafony table:jobs

# Generate failed_jobs table migration
php bin/larafony table:failed-jobs

# Run migrations
php bin/larafony migrate
```

### Jobs Table Schema

The `jobs` table stores queued jobs:

```php
// Generated migration: database/migrations/YYYY_MM_DD_HHMMSS_create_jobs_table.php
Schema::create('jobs', function (Blueprint $table) {
    $table->uuid('id')->primary();          // UUID primary key
    $table->text('payload');                // Serialized job data
    $table->string('queue')->nullable();    // Queue name
    $table->integer('attempts')->default(0); // Attempt counter
    $table->datetime('reserved_at')->nullable(); // When job was reserved
    $table->datetime('available_at');       // When job becomes available
    $table->datetime('created_at');         // Job creation time

    $table->index(['queue', 'available_at']); // For efficient querying
});
```

**Key Schema Features:**
- **UUID Primary Key**: Enabled via `use_uuid = true` in Job entity for distributed systems
- **Indexed Querying**: Composite index on `queue` and `available_at` for fast job retrieval
- **Reserved At**: Tracks when a job is being processed (for job locking in future implementations)
- **Attempts Counter**: Useful for retry logic and debugging

### Failed Jobs Table Schema

The `failed_jobs` table stores jobs that threw exceptions:

```php
// Generated migration: database/migrations/YYYY_MM_DD_HHMMSS_create_failed_jobs_table.php
Schema::create('failed_jobs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('uuid')->unique();       // Unique identifier for retry/forget
    $table->string('connection');           // Queue connection name
    $table->string('queue');                // Queue name
    $table->longText('payload');            // Serialized job data
    $table->longText('exception');          // Full exception details
    $table->datetime('failed_at');          // Failure timestamp
});
```

**Failed Job Features:**
- **Separate UUID**: Distinct from job ID for reliable failure tracking
- **Full Exception Data**: Stores complete stack trace for debugging
- **Connection Tracking**: Records which queue driver was used

### Configuration

**config/queue.php:**

```php
return [
    'default' => env('QUEUE_CONNECTION', 'database'),

    'connections' => [
        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => 'default',
        ],
    ],

    'failed' => [
        'database' => 'default',
        'table' => 'failed_jobs',
    ],
];
```

**config/schedule.php:**

```php
use Larafony\Framework\Scheduler\CronSchedule;

return [
    \App\Jobs\BackupDatabaseJob::class => CronSchedule::DAILY->at(3, 0),
    \App\Jobs\CleanupTempFilesJob::class => CronSchedule::HOURLY,
    \App\Jobs\SendWeeklyReportJob::class => CronSchedule::MONDAY->at(9, 0),
];
```

## Usage Examples

### Basic Example: Creating and Dispatching a Job

```php
<?php

namespace App\Jobs;

use Larafony\Framework\Scheduler\Attributes\Serialize;
use Larafony\Framework\Scheduler\Job;

class SendWelcomeEmailJob extends Job
{
    public function __construct(
        #[Serialize] private int $userId,
        #[Serialize] private string $email
    ) {}

    public function handle(): void
    {
        // Send welcome email
        $emailService = Application::instance()->get(EmailService::class);
        $emailService->send($this->email, 'Welcome!', 'Welcome to our platform');
    }

    public function handleException(\Throwable $e): void
    {
        // Log the failure
        error_log("Failed to send welcome email to {$this->email}: " . $e->getMessage());
    }
}

// Dispatching the job
use Larafony\Framework\Scheduler\Dispatcher;

$dispatcher = $container->get(Dispatcher::class);

// Immediate dispatch
$jobId = $dispatcher->dispatch(new SendWelcomeEmailJob(123, 'user@example.com'));

// Delayed dispatch (after 5 minutes)
$jobId = $dispatcher->dispatchAfter(300, new SendWelcomeEmailJob(123, 'user@example.com'));

// Batch dispatch
$jobIds = $dispatcher->dispatchBatch(
    new SendWelcomeEmailJob(1, 'user1@example.com'),
    new SendWelcomeEmailJob(2, 'user2@example.com'),
    new SendWelcomeEmailJob(3, 'user3@example.com')
);
```

### Advanced Example: Scheduled Tasks with Cron

```php
<?php

// config/schedule.php
use Larafony\Framework\Scheduler\CronSchedule;
use App\Jobs\DatabaseBackupJob;
use App\Jobs\CleanupTempFilesJob;
use App\Jobs\SendDailyReportJob;
use App\Jobs\GenerateSitemapJob;

return [
    // Run every minute
    HealthCheckJob::class => CronSchedule::EVERY_MINUTE,

    // Run daily at 3:00 AM
    DatabaseBackupJob::class => CronSchedule::DAILY->at(3, 0),

    // Run every Monday at 9:00 AM
    SendWeeklyReportJob::class => CronSchedule::MONDAY->at(9, 0),

    // Run every 15 minutes
    CleanupTempFilesJob::class => CronSchedule::EVERY_FIFTEEN_MINUTES,

    // Run on weekdays at noon
    SendDailyReportJob::class => CronSchedule::WEEKDAYS->at(12, 0),

    // Custom cron expression (every hour at :30)
    GenerateSitemapJob::class => '30 * * * *',

    // Every N minutes using helper
    CacheWarmupJob::class => CronSchedule::everyNMinutes(10),
];

// Set up cron to run schedule:run every minute
// * * * * * cd /var/www/project && php bin/larafony schedule:run >> /dev/null 2>&1
```

### Advanced Example: Failed Job Recovery

```php
<?php

use Larafony\Framework\Scheduler\FailedJobRepository;

$failedJobRepo = $container->get(FailedJobRepository::class);

// List all failed jobs
$failedJobs = $failedJobRepo->all();
foreach ($failedJobs as $failedJob) {
    echo "UUID: {$failedJob->uuid}\n";
    echo "Queue: {$failedJob->queue}\n";
    echo "Failed: {$failedJob->failed_at->format('Y-m-d H:i:s')}\n";
    echo "Exception: " . substr($failedJob->exception, 0, 100) . "...\n\n";
}

// Retry a specific failed job
$dispatcher = $container->get(Dispatcher::class);
$job = $failedJobRepo->retry('some-uuid-here');
if ($job) {
    $dispatcher->dispatch($job);
    echo "Job retried successfully\n";
}

// Prune old failed jobs (older than 7 days)
$count = $failedJobRepo->prune(168); // 168 hours = 7 days
echo "Pruned {$count} old failed jobs\n";

// Flush all failed jobs
$failedJobRepo->flush();
```

### Advanced Example: Queue Worker Configuration

```php
<?php

// Run worker continuously (typical production setup)
// php bin/larafony queue:work

// Process only one job then exit (useful for testing)
// php bin/larafony queue:work --once

// Process max 100 jobs then exit (for worker rotation)
// php bin/larafony queue:work --max-jobs=100

// Stop when queue is empty (for batch processing)
// php bin/larafony queue:work --stop-when-empty

// Worker implementation with custom failed job handling
use Larafony\Framework\Scheduler\QueueFactory;
use Larafony\Framework\Scheduler\Workers\QueueWorker;
use Larafony\Framework\Scheduler\FailedJobRepository;

$queue = QueueFactory::make();
$failedJobRepo = new FailedJobRepository();

// Create worker with 0 iterations (infinite loop)
$worker = new QueueWorker(
    queue: $queue,
    iterations: 0,
    failedJobRepository: $failedJobRepo
);

// Start processing
$worker->work();
```

## Console Commands Reference

### Queue Management Commands

#### `queue:work` - Process Queue Jobs

Starts a worker to process jobs from the queue.

```bash
# Basic usage (runs indefinitely)
php bin/larafony queue:work

# Process one job and exit
php bin/larafony queue:work --once

# Process specific queue
php bin/larafony queue:work --queue=emails

# Process max 100 jobs then exit
php bin/larafony queue:work --max-jobs=100

# Stop when queue is empty
php bin/larafony queue:work --stop-when-empty
```

**Options:**
- `--queue=<name>` - Specify which queue to process (default: 'default')
- `--once` - Process a single job then exit
- `--max-jobs=<n>` - Maximum number of jobs to process before exiting
- `--stop-when-empty` - Exit when no jobs are available

**Production Usage:**
```bash
# Using supervisor to keep worker running
[program:larafony-worker]
command=php /var/www/app/bin/larafony queue:work --stop-when-empty
autostart=true
autorestart=true
numprocs=3
```

#### `schedule:run` - Run Scheduled Tasks

Evaluates all scheduled tasks and queues those that are due.

```bash
# Run scheduled tasks
php bin/larafony schedule:run
```

**Cron Setup:**
```cron
* * * * * cd /var/www/app && php bin/larafony schedule:run >> /dev/null 2>&1
```

This single cron entry runs every minute and checks if any scheduled tasks are due.

### Failed Job Commands

#### `queue:failed` - List Failed Jobs

Display all failed jobs with their details.

```bash
php bin/larafony queue:failed
```

**Output:**
```
UUID: 550e8400-e29b-41d4-a716-446655440000
Queue: default
Failed: 2024-01-15 14:30:22
Exception: RuntimeException: Connection timeout...
```

#### `queue:retry` - Retry Failed Jobs

Retry one or all failed jobs.

```bash
# Retry specific failed job by UUID
php bin/larafony queue:retry 550e8400-e29b-41d4-a716-446655440000

# Retry all failed jobs
php bin/larafony queue:retry all
```

The job is removed from `failed_jobs` table and re-queued to the appropriate queue.

#### `queue:forget` - Delete Failed Job

Remove a specific failed job from the failed jobs table.

```bash
php bin/larafony queue:forget 550e8400-e29b-41d4-a716-446655440000
```

**Use Case:** When a job failed due to a bug that's been fixed, and you don't want to retry it.

#### `queue:flush` - Clear All Failed Jobs

Delete all failed jobs from the database.

```bash
php bin/larafony queue:flush
```

**Warning:** This is irreversible. Use with caution.

#### `queue:prune` - Remove Old Failed Jobs

Delete failed jobs older than a specified number of hours.

```bash
# Remove failed jobs older than 24 hours (default)
php bin/larafony queue:prune

# Remove failed jobs older than 7 days
php bin/larafony queue:prune --hours=168
```

**Automation:**
```cron
# Clean up old failures daily
0 3 * * * cd /var/www/app && php bin/larafony queue:prune --hours=168
```

### Migration Commands

#### `table:jobs` - Generate Jobs Table Migration

Creates a migration file for the `jobs` table.

```bash
php bin/larafony table:jobs
```

**Generated file:** `database/migrations/YYYY_MM_DD_HHMMSS_create_jobs_table.php`

#### `table:failed-jobs` - Generate Failed Jobs Table Migration

Creates a migration file for the `failed_jobs` table.

```bash
php bin/larafony table:failed-jobs
```

**Generated file:** `database/migrations/YYYY_MM_DD_HHMMSS_create_failed_jobs_table.php`

### Common Workflows

**Initial Setup:**
```bash
# 1. Generate migrations
php bin/larafony table:jobs
php bin/larafony table:failed-jobs

# 2. Run migrations
php bin/larafony migrate

# 3. Start queue worker
php bin/larafony queue:work
```

**Production Monitoring:**
```bash
# Check for failed jobs
php bin/larafony queue:failed

# Retry specific failure after fixing bug
php bin/larafony queue:retry <uuid>

# Clean up old failures weekly
php bin/larafony queue:prune --hours=168
```

**Development Testing:**
```bash
# Process jobs one at a time for debugging
php bin/larafony queue:work --once

# Test scheduled tasks without waiting
php bin/larafony schedule:run
```

## Comparison with Other Frameworks

| Feature | Larafony | Laravel | Symfony |
|---------|----------|---------|---------|
| **Configuration** | Attribute-based (`#[Serialize]`) + Config files | Config files + Class-based | Class-based messages |
| **Cron Scheduling** | Enum presets + fluent API (`CronSchedule::DAILY->at(3, 0)`) | Fluent API in code (`$schedule->daily()->at('03:00')`) | Separate cron expressions or Scheduler component |
| **Queue Drivers** | Database, Redis (extensible) | Database, Redis, SQS, Beanstalkd, many others | AMQP, Doctrine, Redis, many transports |
| **Job Serialization** | Explicit via `#[Serialize]` attribute | Implicit (all constructor params) | Explicit message classes |
| **Failed Jobs** | ORM-based with UUID tracking, CLI commands | Database table with CLI commands + Horizon UI | Retry strategies, failure transports |
| **PSR Compliance** | PSR-11 (Container), PSR-4 (Autoloading) | PSR-11, PSR-4, PSR-7 (HTTP) | Full PSR compliance (PSR-11, PSR-4, PSR-6, PSR-14, PSR-18) |
| **Time Handling** | Custom Clock system (testable) | Carbon library | DateTime (native PHP) |
| **Worker Management** | CLI command with options | CLI command + Horizon dashboard | Messenger worker with supervisor |
| **Job Definition** | Abstract base class with `handleException()` | Implement handle(), optional failed() | Message classes with handlers |
| **Schedule Definition** | Config file with enum presets | routes/console.php with fluent API | Cron expressions or ScheduleBuilder |
| **Approach** | Minimal dependencies, attribute-first | Feature-rich, convention over configuration | Component-based, highly modular |

**Key Differences:**

1. **Serialization Philosophy**: Larafony requires explicit `#[Serialize]` attributes, making it clear which properties will be serialized. Laravel serializes all constructor parameters implicitly, which can lead to unexpected serialization of dependencies. Symfony uses separate message classes, providing the strongest separation.

2. **Schedule Configuration**: Larafony uses enum-based presets (`CronSchedule::DAILY->at(3, 0)`) in config files, combining type safety with configuration. Laravel uses a fluent API directly in code, offering more flexibility but less separation. Symfony typically relies on cron expressions or the Scheduler component.

3. **Failed Job Handling**: All three frameworks support failed job tracking, but Larafony uses ORM entities and UUID-based identification, Laravel uses database with optional Horizon dashboard, and Symfony uses failure transports and retry strategies.

4. **Time Management**: Larafony's custom Clock system provides testable time operations (via `ClockFactory::freeze()`) and stores Clock objects directly in entities. Laravel uses Carbon for rich date manipulation. Symfony uses native DateTime/DateTimeImmutable. Larafony's approach enables seamless time mocking in tests and proper separation between system time and domain time.

5. **Developer Experience**: Larafony prioritizes explicitness and type safety through attributes and enums. Laravel prioritizes developer speed through conventions. Symfony prioritizes flexibility through components.

6. **Queue Infrastructure**: Larafony provides a focused set of queue drivers (database, Redis) with clean abstractions. Laravel offers extensive driver support out-of-the-box. Symfony's Messenger is transport-agnostic with extensive third-party support.

## Testing Queue Jobs

### Time-Based Testing with ClockFactory

Larafony's Clock system makes testing time-dependent queue behavior straightforward:

```php
use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Scheduler\Queue\DatabaseQueue;
use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
{
    protected function setUp(): void
    {
        // Freeze time at a specific moment
        ClockFactory::freeze(new \DateTimeImmutable('2024-01-01 12:00:00'));
    }

    protected function tearDown(): void
    {
        // Reset to real system time
        ClockFactory::reset();
    }

    public function testDelayedJobIsNotAvailableImmediately(): void
    {
        $queue = new DatabaseQueue();

        // Queue a job for 2 hours from now
        $delay = new \DateTime('2024-01-01 14:00:00');
        $jobId = $queue->later($delay, new SendEmailJob('test@example.com'));

        // Job should not be available yet (current time is 12:00)
        $this->assertNull($queue->pop());

        // Advance time to 14:01
        ClockFactory::freeze(new \DateTimeImmutable('2024-01-01 14:01:00'));

        // Now job should be available
        $job = $queue->pop();
        $this->assertInstanceOf(SendEmailJob::class, $job);
    }

    public function testJobsProcessedInOrder(): void
    {
        $queue = new DatabaseQueue();

        // Queue jobs with different availability times
        $queue->later(new \DateTime('2024-01-01 12:03:00'), new JobC());
        $queue->later(new \DateTime('2024-01-01 12:01:00'), new JobA());
        $queue->later(new \DateTime('2024-01-01 12:02:00'), new JobB());

        // Advance time past all jobs
        ClockFactory::freeze(new \DateTimeImmutable('2024-01-01 12:05:00'));

        // Jobs should be processed in order of availability
        $this->assertInstanceOf(JobA::class, $queue->pop());
        $this->assertInstanceOf(JobB::class, $queue->pop());
        $this->assertInstanceOf(JobC::class, $queue->pop());
    }
}
```

**Key Testing Benefits:**
- **Deterministic Tests**: Frozen time ensures tests produce consistent results
- **Fast Execution**: No need to actually wait for delays
- **Edge Case Testing**: Easy to test boundary conditions (midnight, month transitions, etc.)
- **Isolation**: Each test can manipulate time independently

### Testing Scheduled Tasks

```php
public function testCronScheduleIsDue(): void
{
    // Test that a job scheduled for midnight runs at midnight
    ClockFactory::freeze(new \DateTimeImmutable('2024-01-01 00:00:00'));

    $schedule = new Schedule();
    $schedule->cron(CronSchedule::DAILY, new BackupJob());

    $dueEvents = $schedule->dueEvents();
    $this->assertCount(1, $dueEvents);

    // Move time forward - job should not be due again
    ClockFactory::freeze(new \DateTimeImmutable('2024-01-01 00:01:00'));

    $dueEvents = $schedule->dueEvents();
    $this->assertCount(0, $dueEvents);
}
```

---

📚 **Learn More:** This implementation is explained in detail with step-by-step tutorials, tests, and best practices at [masterphp.eu](https://masterphp.eu)
