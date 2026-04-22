<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_group', function (Blueprint $table) {
            $table->foreign('group_id')->references('id')->on('groups')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('event_group', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
        });
    }
};
