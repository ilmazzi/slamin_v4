<?php

namespace Domain\I18n\Support;

final class TranslationTree
{
    /**
     * @param  array<string, mixed>  $tree
     * @return array<string, string>
     */
    public static function flatten(array $tree, string $prefix = ''): array
    {
        $rows = [];

        foreach ($tree as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value)) {
                $rows = [...$rows, ...self::flatten($value, $path)];

                continue;
            }

            if (is_scalar($value) || $value === null) {
                $rows[$path] = $value === null ? '' : (string) $value;
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, string>  $dotted
     * @return array<string, mixed>
     */
    public static function nest(array $dotted): array
    {
        $tree = [];

        foreach ($dotted as $key => $value) {
            $segments = explode('.', (string) $key);
            $cursor = &$tree;

            foreach ($segments as $index => $segment) {
                if ($index === count($segments) - 1) {
                    $cursor[$segment] = $value;

                    continue;
                }

                if (! isset($cursor[$segment]) || ! is_array($cursor[$segment])) {
                    $cursor[$segment] = [];
                }

                $cursor = &$cursor[$segment];
            }

            unset($cursor);
        }

        return $tree;
    }
}
