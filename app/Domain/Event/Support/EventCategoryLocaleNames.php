<?php

namespace Domain\Event\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

final class EventCategoryLocaleNames
{
    /**
     * @var array<string, string>
     */
    public const SLUG_TO_KEY = [
        'concerto-musica' => 'concert',
        'conferenza-tavola-rotonda' => 'conference',
        'concorso' => 'contest',
        'festival' => 'festival',
        'laboratorio-corso' => 'workshop',
        'open-mic' => 'open_mic',
        'poesia-piu-altra-arte' => 'poetry_art',
        'poetry-slam' => 'poetry_slam',
        'presentazione-libro' => 'book_presentation',
        'reading' => 'reading',
        'residenza' => 'residency',
        'spoken-word' => 'spoken_word',
    ];

    public static function nameFor(string $slug, string $locale): ?string
    {
        $key = self::SLUG_TO_KEY[$slug] ?? null;
        if ($key === null) {
            return null;
        }

        $path = lang_path($locale.'/events.php');
        if (! File::exists($path)) {
            return null;
        }

        $translations = require $path;
        $name = data_get($translations, 'create_page.categories.'.$key);

        if (! is_string($name) || $name === '') {
            return null;
        }

        return $name;
    }

    public static function fillTranslations(bool $overwrite = false): void
    {
        $now = now();
        $locales = collect(File::directories(lang_path()))
            ->map(fn (string $dir): string => basename($dir))
            ->filter()
            ->values();

        $categories = DB::table('event_categories')->get(['id', 'slug']);

        foreach ($categories as $category) {
            foreach ($locales as $locale) {
                $name = self::nameFor((string) $category->slug, $locale);
                if ($name === null) {
                    continue;
                }

                $query = [
                    'event_category_id' => $category->id,
                    'locale' => $locale,
                ];

                if (! $overwrite && DB::table('event_category_translations')->where($query)->exists()) {
                    continue;
                }

                DB::table('event_category_translations')->updateOrInsert($query, [
                    'name' => $name,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]);
            }
        }
    }
}
