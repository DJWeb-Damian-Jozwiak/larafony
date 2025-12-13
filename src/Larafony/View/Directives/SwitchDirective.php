<?php

declare(strict_types=1);

namespace Larafony\Framework\View\Directives;

class SwitchDirective extends Directive
{
    public function compile(string $content): string
    {
        return $content |> $this->compileSwitch(...)
            |> $this->compileCase(...)
            |> $this->compileDefault(...)
            |> $this->compileBreak(...)
            |> $this->compileEndSwitch(...);
    }

    public function compileSwitch(string $content): string
    {
        return $this->compilePattern(
            '/\@switch\s*\((.*?)\)/',
            $content,
            static fn ($matches) => "<?php switch({$matches[1]}): ?>"
        );
    }

    public function compileCase(string $content): string
    {
        return $this->compilePattern(
            '/\@case\s*\((.*?)\)/',
            $content,
            static fn ($matches) => "<?php case {$matches[1]}: ?>"
        );
    }

    public function compileDefault(string $content): string
    {
        return $this->compilePattern(
            '/\@default/',
            $content,
            static fn () => '<?php default: ?>'
        );
    }

    public function compileBreak(string $content): string
    {
        return $this->compilePattern(
            '/\@break/',
            $content,
            static fn () => '<?php break; ?>'
        );
    }

    public function compileEndSwitch(string $content): string
    {
        return $this->compilePattern(
            '/\@endswitch/',
            $content,
            static fn () => '<?php endswitch; ?>'
        );
    }
}
