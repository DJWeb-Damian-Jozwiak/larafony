<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Core\Support;

use Larafony\Framework\Core\Support\Pluralizer;
use PHPUnit\Framework\TestCase;

final class PluralizerTest extends TestCase
{
    public function testRegularPluralWithS(): void
    {
        $this->assertSame('users', Pluralizer::pluralize('user'));
        $this->assertSame('posts', Pluralizer::pluralize('post'));
        $this->assertSame('articles', Pluralizer::pluralize('article'));
    }

    public function testWordsEndingInSShChXZ(): void
    {
        $this->assertSame('boxes', Pluralizer::pluralize('box'));
        $this->assertSame('classes', Pluralizer::pluralize('class'));
        $this->assertSame('churches', Pluralizer::pluralize('church'));
        $this->assertSame('dishes', Pluralizer::pluralize('dish'));
        $this->assertSame('buzzes', Pluralizer::pluralize('buzz'));
    }

    public function testWordsEndingInConsonantY(): void
    {
        $this->assertSame('categories', Pluralizer::pluralize('category'));
        $this->assertSame('cities', Pluralizer::pluralize('city'));
        $this->assertSame('countries', Pluralizer::pluralize('country'));
        $this->assertSame('companies', Pluralizer::pluralize('company'));
    }

    public function testWordsEndingInVowelY(): void
    {
        $this->assertSame('days', Pluralizer::pluralize('day'));
        $this->assertSame('boys', Pluralizer::pluralize('boy'));
        $this->assertSame('keys', Pluralizer::pluralize('key'));
    }

    public function testWordsEndingInFOrFe(): void
    {
        $this->assertSame('leaves', Pluralizer::pluralize('leaf'));
        $this->assertSame('lives', Pluralizer::pluralize('life'));
        $this->assertSame('knives', Pluralizer::pluralize('knife'));
        $this->assertSame('wives', Pluralizer::pluralize('wife'));
    }

    public function testWordsEndingInConsonantO(): void
    {
        $this->assertSame('potatoes', Pluralizer::pluralize('potato'));
        $this->assertSame('tomatoes', Pluralizer::pluralize('tomato'));
        $this->assertSame('heroes', Pluralizer::pluralize('hero'));
    }

    public function testIrregularPlurals(): void
    {
        $this->assertSame('people', Pluralizer::pluralize('person'));
        $this->assertSame('men', Pluralizer::pluralize('man'));
        $this->assertSame('women', Pluralizer::pluralize('woman'));
        $this->assertSame('children', Pluralizer::pluralize('child'));
        $this->assertSame('teeth', Pluralizer::pluralize('tooth'));
        $this->assertSame('feet', Pluralizer::pluralize('foot'));
        $this->assertSame('mice', Pluralizer::pluralize('mouse'));
        $this->assertSame('geese', Pluralizer::pluralize('goose'));
    }

    public function testLatinPlurals(): void
    {
        $this->assertSame('cacti', Pluralizer::pluralize('cactus'));
        $this->assertSame('foci', Pluralizer::pluralize('focus'));
        $this->assertSame('analyses', Pluralizer::pluralize('analysis'));
        $this->assertSame('bases', Pluralizer::pluralize('basis'));
        $this->assertSame('crises', Pluralizer::pluralize('crisis'));
        $this->assertSame('phenomena', Pluralizer::pluralize('phenomenon'));
        $this->assertSame('criteria', Pluralizer::pluralize('criterion'));
    }

    public function testUncountableWords(): void
    {
        $this->assertSame('equipment', Pluralizer::pluralize('equipment'));
        $this->assertSame('information', Pluralizer::pluralize('information'));
        $this->assertSame('rice', Pluralizer::pluralize('rice'));
        $this->assertSame('money', Pluralizer::pluralize('money'));
        $this->assertSame('species', Pluralizer::pluralize('species'));
        $this->assertSame('fish', Pluralizer::pluralize('fish'));
        $this->assertSame('sheep', Pluralizer::pluralize('sheep'));
        $this->assertSame('deer', Pluralizer::pluralize('deer'));
        $this->assertSame('police', Pluralizer::pluralize('police'));
    }

    public function testCountParameterOne(): void
    {
        $this->assertSame('user', Pluralizer::pluralize('user', 1));
        $this->assertSame('person', Pluralizer::pluralize('person', 1));
        $this->assertSame('category', Pluralizer::pluralize('category', 1));
    }

    public function testCountParameterMultiple(): void
    {
        $this->assertSame('users', Pluralizer::pluralize('user', 2));
        $this->assertSame('users', Pluralizer::pluralize('user', 5));
        $this->assertSame('people', Pluralizer::pluralize('person', 10));
    }

    public function testCasePreservationUppercase(): void
    {
        $this->assertSame('PEOPLE', Pluralizer::pluralize('PERSON'));
        $this->assertSame('MEN', Pluralizer::pluralize('MAN'));
        $this->assertSame('WOMEN', Pluralizer::pluralize('WOMAN'));
    }

    public function testCasePreservationCapitalized(): void
    {
        $this->assertSame('People', Pluralizer::pluralize('Person'));
        $this->assertSame('Men', Pluralizer::pluralize('Man'));
        $this->assertSame('Children', Pluralizer::pluralize('Child'));
    }

    public function testCasePreservationLowercase(): void
    {
        $this->assertSame('people', Pluralizer::pluralize('person'));
        $this->assertSame('men', Pluralizer::pluralize('man'));
        $this->assertSame('children', Pluralizer::pluralize('child'));
    }

    public function testRealWorldExamples(): void
    {
        // Common model names (case preserved)
        $this->assertSame('Users', Pluralizer::pluralize('User'));
        $this->assertSame('Posts', Pluralizer::pluralize('Post'));
        $this->assertSame('Categories', Pluralizer::pluralize('Category'));
        $this->assertSame('Companies', Pluralizer::pluralize('Company'));
        $this->assertSame('Addresses', Pluralizer::pluralize('Address'));

        // Edge cases (lowercase)
        $this->assertSame('blog_posts', Pluralizer::pluralize('blog_post'));
        $this->assertSame('user_profiles', Pluralizer::pluralize('user_profile'));
    }
}
