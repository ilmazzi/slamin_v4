<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_availability_options', function (Blueprint $table) {
            $table->timestamp('finalized_at')->nullable()->after('description');
            $table->index(['event_id', 'finalized_at']);
        });
    }

    public function down(): void
    {
        Schema::table('event_availability_options', function (Blueprint $table) {
            $table->dropIndex(['event_id', 'finalized_at']);
            $table->dropColumn('finalized_at');
        });
    }
};
