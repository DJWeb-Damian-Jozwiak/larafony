# Chapter 3: Clock System

> Part of the Larafony Framework - A modern PHP 8.5 framework built from scratch
>
> 📚 Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)

## Overview

Chapter 3 introduces a comprehensive time management system to the Larafony framework, implementing PSR-20 Clock specification with powerful testing capabilities. This component provides a clean, testable abstraction over PHP's datetime functions, making it easy to freeze, manipulate, and control time in both production and testing environments.

The implementation focuses on three core concepts: the **SystemClock** for production use with real system time, the **FrozenClock** for testing with time manipulation capabilities (travel, freeze), and the **ClockFactory** providing a convenient static API with strategy pattern for easy global clock swapping. This architecture eliminates the common problem of untestable code that directly calls `new DateTime('now')`.

The system is fully compatible with PSR-20 ClockInterface while extending it with practical features like timezone support, common date format enums, time comparison methods (isPast, isFuture, isToday), and Carbon-compatible testing API (setTestNow/withTestNow). All implementations use PHP 8.5's modern features including enums, readonly properties, and match expressions.

## Key Components

### Clock Interface and Implementations

- **Clock** (interface) - Extended PSR-20 interface with format(), timestamp(), isPast(), isFuture(), and isToday() methods
- **SystemClock** - Production clock using real system time with optional timezone support and Carbon-compatible setTestNow() API
- **FrozenClock** - Testing clock with time travel methods: freeze(), travel(), travelSeconds(), travelMinutes(), travelHours(), travelDays()

### Factory and Configuration

- **ClockFactory** - Static factory with strategy pattern for global clock management (provides instance(), freeze(), reset(), timezone(), and convenience methods)
- **TimeFormat** (enum) - Predefined date/time formats (ATOM, ISO8601, RFC7231, DATE, TIME, DATETIME, POSTGRES, etc.)
- **Timezone** (enum) - Common timezone constants (UTC, EUROPE_WARSAW, AMERICA_NEW_YORK, ASIA_TOKYO, etc.)

## PSR Standards Implemented

- **PSR-20**: Clock Interface - Full implementation of the official ClockInterface from psr/clock package
- **PSR-4**: Autoloading for `Larafony\Framework\Clock\` namespace
- **Type Safety**: Strict typing with `declare(strict_types=1)` and readonly properties throughout
- **Immutability**: All implementations use `DateTimeImmutable` ensuring immutable datetime values

## New Attributes

This chapter doesn't introduce new PHP attributes, but demonstrates extensive use of PHP 8.5 features:

- `enum` for TimeFormat and Timezone with string backing values
- `readonly` properties in SystemClock for timezone configuration
- Union types like `TimeFormat|string` and `DateTimeImmutable|\DateTimeInterface|string|null`
- `match` expressions for type conversion
- Intersection types like `(Clock & ClockInterface)|null` in ClockFactory

## Usage Examples

### Basic Example - Production Use

```php
<?php

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Clock\Enums\TimeFormat;
use Larafony\Framework\Clock\Enums\Timezone;

require_once __DIR__ . '/vendor/autoload.php';

// Get current time
$now = ClockFactory::now();
echo $now->format('Y-m-d H:i:s'); // 2025-10-22 17:30:45

// Format with enum
echo ClockFactory::format(TimeFormat::DATETIME); // 2025-10-22 17:30:45
echo ClockFactory::format(TimeFormat::ISO8601); // 2025-10-22T17:30:45+0200

// Get timestamp
echo ClockFactory::timestamp(); // 1729611045

// Time comparisons
$yesterday = new DateTimeImmutable('yesterday');
$tomorrow = new DateTimeImmutable('tomorrow');

echo ClockFactory::isPast($yesterday); // true
echo ClockFactory::isFuture($tomorrow); // true
echo ClockFactory::isToday($now); // true
```

### Advanced Example - Testing with Frozen Time

```php
<?php

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Clock\FrozenClock;

// Freeze time at a specific moment
ClockFactory::freeze('2025-01-01 12:00:00');

echo ClockFactory::now(); // 2025-01-01 12:00:00
echo ClockFactory::now(); // 2025-01-01 12:00:00 (always the same)

// Travel forward in time
$clock = ClockFactory::instance();
if ($clock instanceof FrozenClock) {
    $clock->travelHours(2);
    echo ClockFactory::now(); // 2025-01-01 14:00:00

    $clock->travelDays(7);
    echo ClockFactory::now(); // 2025-01-08 14:00:00

    // Travel backward
    $clock->travelMinutes(-30);
    echo ClockFactory::now(); // 2025-01-08 13:30:00
}

// Reset to real time
ClockFactory::reset();
echo ClockFactory::now(); // Current system time
```

### Testing Example - PHPUnit Integration

```php
<?php

use Larafony\Framework\Clock\ClockFactory;
use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{
    protected function setUp(): void
    {
        // Freeze time before each test
        ClockFactory::freeze('2025-01-15 10:00:00');
    }

    protected function tearDown(): void
    {
        // Reset after each test
        ClockFactory::reset();
    }

    public function testOrderExpiration(): void
    {
        $order = new Order();
        $order->expiresAt = ClockFactory::now()->modify('+1 hour');

        // Order not expired yet
        $this->assertFalse($order->isExpired());

        // Travel 30 minutes - still not expired
        ClockFactory::instance()->travelMinutes(30);
        $this->assertFalse($order->isExpired());

        // Travel another 31 minutes - now expired
        ClockFactory::instance()->travelMinutes(31);
        $this->assertTrue($order->isExpired());
    }
}
```

### Timezone Support

```php
<?php

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Clock\Enums\Timezone;
use Larafony\Framework\Clock\Enums\TimeFormat;

// Create clocks for different timezones
$warsaw = ClockFactory::timezone(Timezone::EUROPE_WARSAW);
$tokyo = ClockFactory::timezone(Timezone::ASIA_TOKYO);
$newYork = ClockFactory::timezone(Timezone::AMERICA_NEW_YORK);

echo "Warsaw:   " . $warsaw->format(TimeFormat::DATETIME) . "\n";
echo "Tokyo:    " . $tokyo->format(TimeFormat::DATETIME) . "\n";
echo "New York: " . $newYork->format(TimeFormat::DATETIME) . "\n";

// Output:
// Warsaw:   2025-10-22 17:30:45
// Tokyo:    2025-10-23 00:30:45
// New York: 2025-10-22 11:30:45
```

## Implementation Details

### Clock Interface

**Location:** `src/Larafony/Clock/Contracts/Clock.php:15`

**Purpose:** Extended PSR-20 ClockInterface with additional convenience methods for formatting and date comparisons.

**Key Methods:**
- `now(): DateTimeImmutable` - PSR-20 method returning current time
- `format(TimeFormat|string $format): string` - Format current time with enum or custom format
- `timestamp(): int` - Get current Unix timestamp
- `isPast(DateTimeInterface $date): bool` - Check if date is in the past
- `isFuture(DateTimeInterface $date): bool` - Check if date is in the future
- `isToday(DateTimeInterface $date): bool` - Check if date is today

**Design Philosophy:**
Extends PSR-20 with practical methods while maintaining compatibility. Accepts both enum and string for flexibility.

### SystemClock

**Location:** `src/Larafony/Clock/SystemClock.php:17`

**Purpose:** Production clock implementation using real system time with optional timezone configuration.

**Key Features:**
- **Timezone Support:** Optional `DateTimeZone` in constructor, defaults to UTC
- **Carbon Compatibility:** Static `withTestNow()` and `hasTestNow()` methods matching Carbon's testing API
- **Factory Method:** `fromTimezone(Timezone $timezone)` for convenient creation with enum
- **High Precision:** `milliseconds()` and `microseconds()` methods for precise timing
- **Sleep Methods:** `sleep(int $seconds)` and `usleep(int $microseconds)` for delays

**Testing Integration:**
```php
// Compatible with Carbon's API
SystemClock::withTestNow('2025-01-01 12:00:00');
$clock = new SystemClock();
echo $clock->now(); // 2025-01-01 12:00:00

SystemClock::withTestNow(null); // Reset
```

**Dependencies:** None - pure PHP implementation

### FrozenClock

**Location:** `src/Larafony/Clock/FrozenClock.php:13`

**Purpose:** Testing clock with time manipulation capabilities for predictable test scenarios.

**Key Methods:**
- `freeze(): void` - Freeze at current system time
- `withTo(DateTimeImmutable|DateTimeInterface|string $time): void` - Set frozen time
- `travel(string $interval): void` - Travel using DateTimeImmutable::modify() format ("+1 hour")
- `travelSeconds(int $seconds): void` - Travel by seconds (positive or negative)
- `travelMinutes(int $minutes): void` - Travel by minutes
- `travelHours(int $hours): void` - Travel by hours
- `travelDays(int $days): void` - Travel by days

**Constructor Flexibility:**
Accepts `DateTimeImmutable`, `DateTimeInterface`, string, or null (defaults to 'now') with automatic conversion using match expression.

**Usage Pattern:**
```php
$clock = new FrozenClock('2025-01-01 12:00:00');
echo $clock->now(); // 2025-01-01 12:00:00
echo $clock->now(); // 2025-01-01 12:00:00 (same time)

$clock->travelHours(2);
echo $clock->now(); // 2025-01-01 14:00:00

$clock->travelDays(-1); // Travel backward
echo $clock->now(); // 2024-12-31 14:00:00
```

### ClockFactory

**Location:** `src/Larafony/Clock/ClockFactory.php:17`

**Purpose:** Static factory implementing strategy pattern for global clock management with convenient API.

**Key Features:**
- **Strategy Pattern:** Swap clock implementation globally via `withInstance(Clock $clock)`
- **Lazy Initialization:** Creates SystemClock on first `instance()` call
- **Testing Helpers:** `freeze()` creates FrozenClock, `reset()` clears instance
- **Convenience Methods:** Static proxies to instance (now(), format(), timestamp(), isPast(), etc.)

**Methods:**
- `instance(): Clock` - Get current clock (lazy creates SystemClock)
- `withInstance(Clock $clock): void` - Set custom clock implementation
- `reset(): void` - Clear instance, next call creates new SystemClock
- `freeze(DateTimeImmutable|string|null $time): void` - Create and set FrozenClock
- `timezone(Timezone $timezone): Clock` - Create clock with specific timezone

**Static Proxies:**
All Clock interface methods available as static methods: `now()`, `format()`, `timestamp()`, `isPast()`, `isFuture()`, `isToday()`

**Dependencies:** Clock interface, SystemClock, FrozenClock, TimeFormat enum, Timezone enum

**Architecture Benefits:**
- **Testability:** Swap clock globally without dependency injection
- **Convenience:** Static API for quick access
- **Flexibility:** Strategy pattern allows custom implementations

### TimeFormat Enum

**Location:** `src/Larafony/Clock/Enums/TimeFormat.php:7`

**Purpose:** Predefined date/time format constants as backed string enum.

**Available Formats:**
- **Standards:** ATOM, ISO8601, ISO8601_EXPANDED, RFC822, RFC850, RFC7231, RSS, COOKIE
- **Common:** DATE (Y-m-d), TIME (H:i:s), DATETIME (Y-m-d H:i:s)
- **Display:** DATE_SHORT (d/m/Y), DATE_LONG (l, F j, Y), TIME_12H (g:i A), TIME_24H (H:i)
- **Database:** POSTGRES (Y-m-d H:i:s.uP)

### Timezone Enum

**Location:** `src/Larafony/Clock/Enums/Timezone.php:7`

**Purpose:** Common timezone constants as backed string enum.

**Regions Covered:**
- **UTC:** Universal time
- **Europe:** London, Paris, Berlin, Warsaw, Moscow
- **Americas:** New York, Chicago, Denver, Los Angeles, São Paulo
- **Asia:** Tokyo, Shanghai, Hong Kong, Singapore, Dubai, Kolkata
- **Oceania:** Sydney, Melbourne, Auckland

## Comparison with Other Frameworks

| Feature | Larafony | Laravel | Symfony |
|---------|----------|---------|---------|
| **PSR-20 Compliance** | Full PSR-20 implementation | Carbon (not PSR-20) | PSR-20 since Symfony 6.2 |
| **Testing API** | FrozenClock + ClockFactory::freeze() | Carbon::setTestNow() + TestTime package | MockClock with sleep/modify |
| **Time Travel** | travelSeconds/Minutes/Hours/Days | Carbon::setTestNow() or TestTime::addMinute() | MockClock->sleep() or modify() |
| **Timezone Support** | Enum-based with SystemClock::fromTimezone() | Carbon timezone methods | NativeClock with timezone parameter |
| **Static Factory** | ClockFactory with strategy pattern | Carbon static methods | Clock class as PSR-20 wrapper |
| **Format Helpers** | TimeFormat enum with 15+ formats | Carbon format methods | Standard DateTimeInterface::format() |
| **Dependencies** | psr/clock only | nesbot/carbon (heavy) | symfony/clock component |
| **Approach** | PSR-first, enum-driven, testable | Convenient but non-standard | PSR-20 native with component |

**Key Differences:**

- **PSR-20 First:** Larafony builds directly on PSR-20 standard, ensuring framework-agnostic compatibility. Laravel's Carbon predates PSR-20 and doesn't implement it natively.

- **Enum-Driven Design:** Larafony uses PHP 8.5 enums (TimeFormat, Timezone) for type-safe configuration vs. string-based formats in other frameworks.

- **Zero Dependencies:** Only requires `psr/clock` interface (no implementation). Laravel requires the full Carbon library (~100KB). Symfony requires symfony/clock component.

- **Explicit Testing Model:** Larafony separates production (SystemClock) from testing (FrozenClock) clocks. Laravel overloads Carbon with testing features. Symfony uses MockClock but less discoverable.

- **Strategy Pattern Factory:** ClockFactory allows global clock swapping without DI container. Laravel uses static Carbon methods. Symfony requires passing Clock through constructor injection.

- **Backward Travel:** FrozenClock supports negative time travel (e.g., `travelDays(-5)`). Laravel requires manually setting earlier date. Symfony MockClock only moves forward via sleep().

- **Carbon Compatibility:** SystemClock provides `withTestNow()` for Carbon users migrating to Larafony. This bridges the gap between Carbon's testing API and PSR-20.

## Real World Integration

This chapter's features are demonstrated in the demo application with real-world usage examples.

### Demo Application Changes

The demo application was enhanced to display the current time using the Clock system with timezone support and enum-based formatting. This demonstrates practical usage of ClockFactory's static API and enum configuration.

### File Structure
```
demo-app/
└── public/
    └── index.php          # Updated with Clock display on homepage
```

### Implementation Example

**File: `demo-app/public/index.php`**

```php
<?php

declare(strict_types=1);

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Clock\Enums\TimeFormat;
use Larafony\Framework\Clock\Enums\Timezone;
use Larafony\Framework\Clock\SystemClock;
use Larafony\Framework\ErrorHandler\DetailedErrorHandler;
use Uri\Rfc3986\Uri;

require_once __DIR__ . '/../vendor/autoload.php';

// Register error handler from Chapter 2
new DetailedErrorHandler()->register();

// Simple routing
$path = new Uri($_SERVER['REQUEST_URI'])->getPath();

match ($path) {
    '/' => handleHome(),
    '/error' => handleError(),
    '/exception' => handleException(),
    '/fatal' => handleFatal(),
    default => handleNotFound(),
};

function handleHome(): void
{
    echo '<h1>Larafony Framework Demo</h1>';
    echo '<p>Error Handler is active. Try these endpoints:</p>';

    // NEW in Chapter 3: Display current time using Clock system
    // Uses ClockFactory's static API for convenience
    // Demonstrates timezone() factory method with Timezone enum
    // Shows format() with TimeFormat enum for consistent formatting
    echo '<p>Now is ' . ClockFactory::timezone(Timezone::EUROPE_WARSAW)
            ->format(TimeFormat::DATETIME) . '</p>';

    echo '<ul>';
    echo '<li><a href="/error">Trigger E_WARNING</a></li>';
    echo '<li><a href="/exception">Trigger Exception</a></li>';
    echo '<li><a href="/fatal">Trigger Fatal Error</a></li>';
    echo '</ul>';
}

```

**What's happening here:**

1. **Import Clock Components** (lines 5-8): Import ClockFactory for static API access, TimeFormat and Timezone enums for type-safe configuration, and SystemClock class (imported but not directly used - ClockFactory creates it internally).

2. **Static Factory Pattern** (line 30): `ClockFactory::timezone(Timezone::EUROPE_WARSAW)` demonstrates the factory method pattern - creates a SystemClock configured with Warsaw timezone without explicit constructor call.

3. **Enum-Based Configuration** (lines 30-31): Uses `Timezone::EUROPE_WARSAW` enum case instead of string 'Europe/Warsaw', providing IDE autocomplete and type safety. Similarly, `TimeFormat::DATETIME` ensures consistent formatting across the application.

4. **Method Chaining** (lines 30-31): The factory method returns a Clock instance, allowing immediate call to `format()` method. This fluent interface makes the code readable and concise.

5. **No Global State in Production**: Unlike using ClockFactory::freeze() for testing, here we create a new clock instance per call, keeping the code stateless and predictable.

### Running the Demo

```bash
cd framework/demo-app
php8.5 -S localhost:8000 -t public
```

Then visit:
- `http://localhost:8000/` - See homepage with current time displayed

**Expected output:**

When visiting the homepage, you'll see:

```
Larafony Framework Demo

Error Handler is active. Try these endpoints:

Now is 2025-10-22 17:30:45

• Trigger E_WARNING
• Trigger Exception
• Trigger Fatal Error
```

The time will be displayed in Warsaw timezone using the DATETIME format (Y-m-d H:i:s).

### Key Takeaways

- **Simple Static API**: `ClockFactory::timezone(...)->format(...)` provides concise, readable time display without boilerplate
- **Type-Safe Enums**: Using `Timezone::EUROPE_WARSAW` and `TimeFormat::DATETIME` prevents typos and provides IDE autocomplete
- **No Configuration Required**: Works out of the box without config files, service providers, or dependency injection setup
- **PSR-20 Compatible**: Although using static factory for convenience, the underlying implementation is PSR-20 compliant
- **Production-Ready**: Clean separation between production code (SystemClock) and test code (FrozenClock) - no test helpers polluting production
- **Framework Integration**: Shows how Clock integrates naturally with existing components (Error Handler from Chapter 2)

---

📚 **Learn More:** This implementation is explained in detail with step-by-step tutorials, tests, and best practices at [masterphp.eu](https://masterphp.eu)
