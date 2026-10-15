<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gigs', function (Blueprint $table): void {
            $table->index(['status', 'opened_at'], 'gigs_status_opened_at_index');
            $table->index(['status', 'type'], 'gigs_status_type_index');
        });
    }

    public function down(): void
    {
        Schema::table('gigs', function (Blueprint $table): void {
            $table->dropIndex('gigs_status_opened_at_index');
            $table->dropIndex('gigs_status_type_index');
        });
    }
};
