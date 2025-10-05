# Chapter 3: Clock - PSR-20 Time Handling

This chapter covers the implementation of a modern, testable time handling system compatible with PSR-20, serving as a lightweight Carbon replacement.

## Overview

Larafony's Clock package provides:
- **PSR-20 compatible** - Implements `Psr\Clock\ClockInterface`
- **Multiple implementations** - `SystemClock` for production, `FrozenClock` for testing
- **Factory pattern** - Easy mocking with `ClockFactory`
- **Timezone support** - Via `Timezone` enum with popular timezones
- **Format presets** - `TimeFormat` enum with RFC/ISO standards and common formats
- **Zero dependencies** - Pure PHP 8.5

## Architecture

The Clock system consists of:

### 1. Clock Interface (`src/Larafony/Clock/Contracts/Clock.php`)

Extended PSR-20 interface with convenience methods:

```php
interface Clock extends ClockInterface
{
    public function format(TimeFormat|string $format): string;
    public function timestamp(): int;
    public function isPast(\DateTimeInterface $date): bool;
    public function isFuture(\DateTimeInterface $date): bool;
    public function isToday(\DateTimeInterface $date): bool;
}
```

### 2. SystemClock (`src/Larafony/Clock/SystemClock.php`)

Production clock using real system time:

```php
$clock = new SystemClock();
$now = $clock->now();

// With timezone
$clock = SystemClock::fromTimezone(Timezone::EUROPE_WARSAW);
```

### 3. FrozenClock (`src/Larafony/Clock/FrozenClock.php`)

Testing clock with time travel capabilities:

```php
$clock = new FrozenClock('2024-01-15 12:00:00');
$clock->travelDays(5);
$clock->travelHours(3);
```

### 4. ClockFactory (`src/Larafony/Clock/ClockFactory.php`)

Factory with Strategy pattern (Consider this as Laravel's Carbon "Facade") for easy testing:

```php
// Production
$now = ClockFactory::now();

// Testing
ClockFactory::freeze('2024-01-15 12:00:00');
```

## Usage

### Basic Time Operations

```php
use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Clock\Enums\TimeFormat;

// Get current time
$now = ClockFactory::now();

// Format time
$date = ClockFactory::format(TimeFormat::DATE); // 2024-01-15
$time = ClockFactory::format(TimeFormat::TIME); // 14:30:45
$datetime = ClockFactory::format(TimeFormat::DATETIME); // 2024-01-15 14:30:45

// Custom format
$custom = ClockFactory::format('Y/m/d H:i'); // 2024/01/15 14:30

// Timestamp
$timestamp = ClockFactory::timestamp(); // 1705320045
```

### Timezone Support

```php
use Larafony\Framework\Clock\Enums\Timezone;
use Larafony\Framework\Clock\SystemClock;

// Create clock with timezone
$clock = SystemClock::fromTimezone(Timezone::EUROPE_WARSAW);
$now = $clock->now(); // Time in Europe/Warsaw

// Or via factory
$clock = ClockFactory::timezone(Timezone::AMERICA_NEW_YORK);
```

### Date Comparison

```php
use Larafony\Framework\Clock\ClockFactory;

$yesterday = new \DateTimeImmutable('-1 day');
$tomorrow = new \DateTimeImmutable('+1 day');
$today = new \DateTimeImmutable('now');

ClockFactory::isPast($yesterday);   // true
ClockFactory::isFuture($tomorrow);  // true
ClockFactory::isToday($today);      // true
```

### Testing with Frozen Time

```php
use Larafony\Framework\Clock\ClockFactory;
use PHPUnit\Framework\TestCase;

class MyTest extends TestCase
{
    protected function tearDown(): void
    {
        ClockFactory::reset();
        parent::tearDown();
    }

    public function testSomethingWithFrozenTime(): void
    {
        // Freeze time for testing
        ClockFactory::freeze('2024-01-15 12:00:00');

        // Your application code now uses frozen time
        $now = ClockFactory::now();
        $this->assertSame('2024-01-15 12:00:00', $now->format('Y-m-d H:i:s'));

        // All date comparisons work with frozen time
        $future = new \DateTimeImmutable('2024-01-20');
        $this->assertTrue(ClockFactory::isFuture($future));
    }
}
```

### Advanced: Time Travel

```php
use Larafony\Framework\Clock\FrozenClock;

$clock = new FrozenClock('2024-01-15 12:00:00');

// Travel forward
$clock->travelDays(5);      // Now: 2024-01-20 12:00:00
$clock->travelHours(3);     // Now: 2024-01-20 15:00:00
$clock->travelMinutes(30);  // Now: 2024-01-20 15:30:00
$clock->travelSeconds(45);  // Now: 2024-01-20 15:30:45

// Travel backward
$clock->travelDays(-5);     // Go back 5 days
$clock->travelHours(-2);    // Go back 2 hours

// Or use intervals
$clock->travel('+1 week');
$clock->travel('-3 months');
```

### Format Presets

```php
use Larafony\Framework\Clock\Enums\TimeFormat;
use Larafony\Framework\Clock\ClockFactory;

ClockFactory::freeze('2024-01-15 14:30:45 UTC');

// Standard formats
ClockFactory::format(TimeFormat::ISO8601);      // 2024-01-15T14:30:45+0000
ClockFactory::format(TimeFormat::RFC7231);      // Mon, 15 Jan 2024 14:30:45 GMT
ClockFactory::format(TimeFormat::ATOM);         // 2024-01-15T14:30:45+00:00

// Database formats
ClockFactory::format(TimeFormat::POSTGRES);     // 2024-01-15 14:30:45.000000+00:00

// Common formats
ClockFactory::format(TimeFormat::DATE);         // 2024-01-15
ClockFactory::format(TimeFormat::TIME);         // 14:30:45
ClockFactory::format(TimeFormat::DATE_SHORT);   // 15/01/2024
ClockFactory::format(TimeFormat::TIME_12H);     // 2:30 PM
ClockFactory::format(TimeFormat::TIME_24H);     // 14:30
```

## Available Timezones

```php
use Larafony\Framework\Clock\Enums\Timezone;

Timezone::UTC
Timezone::EUROPE_LONDON
Timezone::EUROPE_PARIS
Timezone::EUROPE_BERLIN
Timezone::EUROPE_WARSAW
Timezone::EUROPE_MOSCOW
Timezone::AMERICA_NEW_YORK
Timezone::AMERICA_CHICAGO
Timezone::AMERICA_DENVER
Timezone::AMERICA_LOS_ANGELES
Timezone::AMERICA_SAO_PAULO
Timezone::ASIA_TOKYO
Timezone::ASIA_SHANGHAI
Timezone::ASIA_HONG_KONG
Timezone::ASIA_SINGAPORE
Timezone::ASIA_DUBAI
Timezone::ASIA_KOLKATA
Timezone::AUSTRALIA_SYDNEY
Timezone::AUSTRALIA_MELBOURNE
Timezone::PACIFIC_AUCKLAND
```

## Real-World Example

```php
use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Clock\Enums\TimeFormat;

class OrderService
{
    public function createOrder(array $items): Order
    {
        $order = new Order();
        $order->items = $items;
        $order->createdAt = ClockFactory::now();
        $order->formattedDate = ClockFactory::format(TimeFormat::DATETIME);

        return $order;
    }

    public function isOrderExpired(Order $order): bool
    {
        $expiryDate = $order->createdAt->modify('+30 days');
        return ClockFactory::isPast($expiryDate);
    }
}

// In tests
class OrderServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        ClockFactory::reset();
        parent::tearDown();
    }

    public function testOrderExpiration(): void
    {
        ClockFactory::freeze('2024-01-15 12:00:00');

        $service = new OrderService();
        $order = $service->createOrder(['item1', 'item2']);

        // Initially not expired
        $this->assertFalse($service->isOrderExpired($order));

        // Travel 31 days forward
        ClockFactory::freeze('2024-02-16 12:00:00');

        // Now expired
        $this->assertTrue($service->isOrderExpired($order));
    }
}
```

## Testing

Run the Clock tests:

```bash
cd framework
composer test -- tests/Larafony/Clock/
```

All Clock tests:
- `SystemClockTest` - 35 tests
- `FrozenClockTest` - 48 tests
- `ClockFactoryTest` - 23 tests

## Key Differences from Carbon

| Feature | Carbon | Larafony Clock |
|---------|--------|----------------|
| Dependencies | Heavy (symfony/translation, etc.) | Zero (PSR-20 only) |
| Testing | `Carbon::setTestNow()` | `ClockFactory::freeze()` |
| Timezone | String-based | Type-safe enum |
| Format | String constants | Type-safe enum + strings |
| Time travel | Limited | Full support in FrozenClock |
| PSR-20 | ✓ | ✓ |
| Strategy Pattern | ✗ | ✓ (ClockFactory) |

## Best Practices

1. **Use ClockFactory in application code**
   ```php
   // Good
   $now = ClockFactory::now();

   // Avoid (harder to test)
   $now = new \DateTimeImmutable('now');
   ```

2. **Always reset in tearDown()**
   ```php
   protected function tearDown(): void
   {
       ClockFactory::reset();
       parent::tearDown();
   }
   ```

3. **Use enums for type safety**
   ```php
   // Good
   ClockFactory::format(TimeFormat::ISO8601);

   // Okay (but less type-safe)
   ClockFactory::format('Y-m-d\TH:i:sO');
   ```

## Related Documentation

- [Framework README](../../README.md)
- [Chapter 1: Project Setup](./chapter1.md)
- [Chapter 2: Error Handling](./chapter2.md)

## References

- [PSR-20: Clock](https://www.php-fig.org/psr/psr-20/)
- [PHP DateTimeImmutable](https://www.php.net/manual/en/class.datetimeimmutable.php)
- [PHP ext-uri](https://www.php.net/manual/en/book.uri.php)
