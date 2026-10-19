<?php

use Domain\Share\Support\ShareCardLogo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function groupTestCategory(): \Domain\Group\Models\GroupCategory
{
    $category = \Domain\Group\Models\GroupCategory::query()->firstOrCreate(
        ['slug' => 'progetto-artistico'],
        [
            'is_active' => true,
            'sort_order' => 1,
        ],
    );

    if ($category->translations()->doesntExist()) {
        $category->translations()->createMany([
            ['locale' => 'it', 'name' => 'Progetto artistico'],
            ['locale' => 'en', 'name' => 'Artistic project'],
        ]);
    }

    return $category;
}

/**
 * Sample points inside the official share-card wordmark (white letter + red mark).
 *
 * @return array{0: int, 1: int, 2: int, 3: int}
 */
function shareCardLogoSamplePoints(int $canvasWidth, ?int $canvasHeight = null): array
{
    $canvasHeight ??= $canvasWidth;
    $x = ShareCardLogo::destX();
    $y = ShareCardLogo::destY($canvasHeight);
    $scaleX = ShareCardLogo::DISPLAY_WIDTH / ShareCardLogo::SOURCE_WIDTH;
    $scaleY = ShareCardLogo::displayHeight() / ShareCardLogo::SOURCE_HEIGHT;

    return [
        (int) round($x + (55 * $scaleX)),
        (int) round($y + (22 * $scaleY)),
        (int) round($x + (158 * $scaleX)),
        (int) round($y + (66 * $scaleY)),
    ];
}
