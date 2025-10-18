<?php

declare(strict_types=1);

namespace Larafony\Framework\Core\Support;

use Larafony\Framework\Core\Support\StrHelpers\PreserveCase;
use Larafony\Framework\Core\Support\StrHelpers\RegularPluralize;

/**
 * Simple English pluralizer with support for irregular forms.
 *
 * Based on common English pluralization rules and irregular nouns.
 */
final class Pluralizer
{
    /**
     * Irregular plural forms.
     */
    private const array IRREGULAR = [
        'person' => 'people',
        'man' => 'men',
        'woman' => 'women',
        'child' => 'children',
        'tooth' => 'teeth',
        'foot' => 'feet',
        'mouse' => 'mice',
        'goose' => 'geese',
        'ox' => 'oxen',
        'leaf' => 'leaves',
        'life' => 'lives',
        'knife' => 'knives',
        'wife' => 'wives',
        'half' => 'halves',
        'shelf' => 'shelves',
        'wolf' => 'wolves',
        'calf' => 'calves',
        'loaf' => 'loaves',
        'potato' => 'potatoes',
        'tomato' => 'tomatoes',
        'hero' => 'heroes',
        'echo' => 'echoes',
        'veto' => 'vetoes',
        'cactus' => 'cacti',
        'focus' => 'foci',
        'fungus' => 'fungi',
        'nucleus' => 'nuclei',
        'radius' => 'radii',
        'stimulus' => 'stimuli',
        'syllabus' => 'syllabi',
        'analysis' => 'analyses',
        'basis' => 'bases',
        'crisis' => 'crises',
        'diagnosis' => 'diagnoses',
        'thesis' => 'theses',
        'phenomenon' => 'phenomena',
        'criterion' => 'criteria',
        'datum' => 'data',
    ];

    /**
     * Uncountable words (same in singular and plural).
     */
    private const array UNCOUNTABLE = [
        'equipment',
        'information',
        'rice',
        'money',
        'species',
        'series',
        'fish',
        'sheep',
        'deer',
        'moose',
        'swine',
        'buffalo',
        'shrimp',
        'trout',
        'salmon',
        'police',
        'cattle',
        'news',
        'advice',
        'evidence',
        'furniture',
        'luggage',
        'bread',
        'butter',
        'music',
        'homework',
        'software',
        'hardware',
    ];

    /**
     * Pluralize a word.
     *
     * @param string $word Word to pluralize
     * @param int $count Count (if 1, returns singular form)
     *
     * @return string Pluralized word
     */
    public static function pluralize(string $word, int $count = 2): string
    {
        // If count is 1, return singular
        if ($count === 1) {
            return $word;
        }

        $lowercased = strtolower($word);

        // Check if uncountable
        if (in_array($lowercased, self::UNCOUNTABLE, true)) {
            return $word;
        }

        // Check irregular forms
        if (isset(self::IRREGULAR[$lowercased])) {
            return PreserveCase::execute($word, self::IRREGULAR[$lowercased]);
        }

        // Apply rules
        return RegularPluralize::execute($word);
    }
}
