<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Larafony\Console\Formatters\Styles;

use Larafony\Framework\Console\Formatters\Styles\DangerStyle;
use Larafony\Framework\Console\Formatters\Styles\InfoStyle;
use Larafony\Framework\Console\Formatters\Styles\NormalStyle;
use Larafony\Framework\Console\Formatters\Styles\SuccessStyle;
use Larafony\Framework\Console\Formatters\Styles\WarningStyle;
use PHPUnit\Framework\TestCase;

final class StylesTest extends TestCase
{
    public function testDangerStyleAppliesRedColor(): void
    {
        $style = new DangerStyle();
        $result = $style->apply('Error');

        $this->assertSame("\033[31mError\033[0m", $result);
    }

    public function testInfoStyleAppliesCyanColor(): void
    {
        $style = new InfoStyle();
        $result = $style->apply('Information');

        $this->assertSame("\033[36mInformation\033[0m", $result);
    }

    public function testSuccessStyleAppliesGreenColor(): void
    {
        $style = new SuccessStyle();
        $result = $style->apply('Success');

        $this->assertSame("\033[32mSuccess\033[0m", $result);
    }

    public function testWarningStyleAppliesYellowColor(): void
    {
        $style = new WarningStyle();
        $result = $style->apply('Warning');

        $this->assertSame("\033[33mWarning\033[0m", $result);
    }

    public function testNormalStyleReturnsPlainText(): void
    {
        $style = new NormalStyle();
        $result = $style->apply('Plain text');

        $this->assertSame('Plain text', $result);
    }

    public function testStylesHandleEmptyStrings(): void
    {
        $dangerStyle = new DangerStyle();
        $this->assertSame("\033[31m\033[0m", $dangerStyle->apply(''));

        $normalStyle = new NormalStyle();
        $this->assertSame('', $normalStyle->apply(''));
    }

    public function testStylesHandleMultilineText(): void
    {
        $style = new SuccessStyle();
        $text = "Line 1\nLine 2\nLine 3";
        $result = $style->apply($text);

        $this->assertStringContainsString("\033[32m", $result);
        $this->assertStringContainsString("Line 1\nLine 2\nLine 3", $result);
        $this->assertStringContainsString("\033[0m", $result);
    }
}
