<?php

declare(strict_types=1);

use PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineLengthSniff;
use PHP_CodeSniffer\Standards\PSR2\Sniffs\Classes\PropertyDeclarationSniff;
use PhpCsFixer\Fixer\Whitespace\NoSpacesAroundOffsetFixer;
use SlevomatCodingStandard\Sniffs\TypeHints\DisallowMixedTypeHintSniff;

return [

    'preset' => 'default',

    'exclude' => [
        'vendor',
        'tests',
        '*/vendor',
        '*/tests',
        'http/vendor',
        'container/vendor',
        'database/vendor',
        'routing/vendor',
        'session/vendor',
        'view/vendor',
    ],

    'add' => [],

    'remove' => [
        PhpCsFixer\Fixer\ArrayNotation\NormalizeIndexBraceFixer::class,
        PropertyDeclarationSniff::class,
        SlevomatCodingStandard\Sniffs\Classes\ForbiddenPublicPropertySniff::class,
        SlevomatCodingStandard\Sniffs\TypeHints\ParameterTypeHintSniff::class,
        SlevomatCodingStandard\Sniffs\TypeHints\ReturnTypeHintSniff::class,
        NunoMaduro\PhpInsights\Domain\Insights\ForbiddenGlobals::class,
        NunoMaduro\PhpInsights\Domain\Insights\ForbiddenNormalClasses::class,
        DisallowMixedTypeHintSniff::class,
        NoSpacesAroundOffsetFixer::class,
    ],

    'config' => [
        LineLengthSniff::class => [
            'lineLimit' => 120,
            'absoluteLineLimit' => 0,
        ],
    ],
];
