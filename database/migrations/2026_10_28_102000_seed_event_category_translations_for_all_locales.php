<?php

use Domain\Event\Support\EventCategoryLocaleNames;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        EventCategoryLocaleNames::fillTranslations(overwrite: false);
    }

    public function down(): void
    {
        // Keep existing translations: they may include admin edits.
    }
};
