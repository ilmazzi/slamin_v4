<?php

namespace Domain\I18n\DTOs;

use App\Http\Requests\Api\V1\I18n\ImportI18nTranslationsRequest;
use Domain\I18n\Support\TranslationTree;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class ImportTranslationsData
{
    /**
     * @param  array<string, string>  $translations
     */
    public function __construct(
        public readonly string $locale,
        public readonly array $translations,
        public readonly bool $overwrite,
        public readonly bool $createKeys,
    ) {}

    public static function fromRequest(ImportI18nTranslationsRequest $request): self
    {
        $translations = [];

        if ($request->hasFile('file')) {
            $translations = self::parseFile($request->file('file'));
        } elseif (is_array($request->input('translations'))) {
            $translations = self::normalize($request->input('translations'));
        }

        return new self(
            locale: (string) $request->validated('locale'),
            translations: $translations,
            overwrite: $request->boolean('overwrite'),
            createKeys: $request->boolean('create_keys'),
        );
    }

    /**
     * @return array<string, string>
     */
    private static function parseFile(UploadedFile $file): array
    {
        $contents = (string) $file->get();
        $extension = strtolower((string) $file->getClientOriginalExtension());

        if ($extension === 'csv' || str_starts_with(ltrim($contents), 'key,')) {
            return self::parseCsv($contents);
        }

        $decoded = json_decode($contents, true);
        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'file' => [__('i18n.panel.import_invalid')],
            ]);
        }

        if (isset($decoded['catalog']) && is_array($decoded['catalog'])) {
            return self::normalize($decoded['catalog']);
        }

        if (isset($decoded['translations']) && is_array($decoded['translations'])) {
            return self::normalize($decoded['translations']);
        }

        return self::normalize($decoded);
    }

    /**
     * @return array<string, string>
     */
    private static function parseCsv(string $contents): array
    {
        $rows = [];
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $contents);
        rewind($handle);

        $header = fgetcsv($handle);
        $keyIndex = 0;
        $valueIndex = 1;
        if (is_array($header)) {
            $normalized = array_map(fn ($column) => strtolower(trim((string) $column)), $header);
            if (in_array('key', $normalized, true)) {
                $keyIndex = array_search('key', $normalized, true);
                $valueIndex = array_search('value', $normalized, true);
                if ($valueIndex === false) {
                    $valueIndex = 1;
                }
            } else {
                rewind($handle);
            }
        }

        while (($columns = fgetcsv($handle)) !== false) {
            $key = trim((string) ($columns[$keyIndex] ?? ''));
            $value = (string) ($columns[$valueIndex] ?? '');
            if ($key !== '') {
                $rows[$key] = $value;
            }
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @param  array<int|string, mixed>  $payload
     * @return array<string, string>
     */
    private static function normalize(array $payload): array
    {
        if ($payload === []) {
            return [];
        }

        $first = $payload[0] ?? null;
        if (is_array($first) && array_key_exists('key', $first)) {
            $rows = [];
            foreach ($payload as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $key = trim((string) ($item['key'] ?? ''));
                if ($key === '') {
                    continue;
                }
                $rows[$key] = (string) ($item['value'] ?? '');
            }

            return $rows;
        }

        return TranslationTree::flatten($payload);
    }
}
