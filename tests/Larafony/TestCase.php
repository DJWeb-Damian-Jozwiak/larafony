<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests;

use Larafony\Framework\Web\Application;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Application::empty();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Application::empty();
    }
}
