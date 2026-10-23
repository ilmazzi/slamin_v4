<?php

namespace Domain\Event\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class EventGallery
{
    public const MAX_IMAGES = 8;

    public static function storeFiles(array $files): array
    {
        return collect($files)
            ->filter(fn ($file) => $file instanceof UploadedFile)
            ->map(fn (UploadedFile $file) => $file->store('events/gallery', 'public'))
            ->values()
            ->all();
    }

    public static function merge(?array $currentPaths, array $removePaths, array $newFiles): array
    {
        $current = collect($currentPaths ?? [])
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->values();

        $remove = collect($removePaths)
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->values();

        $kept = $current->reject(fn ($path) => $remove->contains($path))->values();

        $remove
            ->filter(fn ($path) => $current->contains($path))
            ->each(fn ($path) => Storage::disk('public')->delete($path));

        $slots = max(0, self::MAX_IMAGES - $kept->count());
        $stored = self::storeFiles(
            collect($newFiles)->take($slots)->all()
        );

        return $kept->concat($stored)->values()->all();
    }
};
