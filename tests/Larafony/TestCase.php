<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests;

use Larafony\Framework\Console\Application as ConsoleApplication;
use Larafony\Framework\Web\Application as WebApplication;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        WebApplication::empty();
        ConsoleApplication::empty();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        WebApplication::empty();
        ConsoleApplication::empty();
        $_SERVER = [];
    }
}
