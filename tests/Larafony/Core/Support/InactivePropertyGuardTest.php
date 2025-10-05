<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Core\Support;

use Larafony\Framework\Core\Support\InactivePropertyGuard;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class InactivePropertyGuardTest extends TestCase
{
    public function testGetReturnsValueWhenNotInactive(): void
    {
        $value = 'test-value';
        $result = InactivePropertyGuard::get($value, false, 'Should not throw');

        $this->assertSame($value, $result);
    }

    public function testGetThrowsExceptionWhenInactive(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Object is inactive');

        InactivePropertyGuard::get('value', true, 'Object is inactive');
    }

    public function testGetWorksWithDifferentTypes(): void
    {
        $object = new \stdClass();
        $array = ['foo' => 'bar'];
        $number = 42;

        $this->assertSame($object, InactivePropertyGuard::get($object, false, 'error'));
        $this->assertSame($array, InactivePropertyGuard::get($array, false, 'error'));
        $this->assertSame($number, InactivePropertyGuard::get($number, false, 'error'));
    }

    public function testGetPreservesCustomErrorMessage(): void
    {
        $customMessage = 'Custom error message for inactive state';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($customMessage);

        InactivePropertyGuard::get('value', true, $customMessage);
    }
}
