<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gigs', function (Blueprint $table): void {
            $table->decimal('proposed_price', 10, 2)->nullable()->after('description');
            $table->string('currency', 3)->nullable()->after('proposed_price');
            $table->timestamp('deadline')->nullable()->after('currency');
            $table->text('special_requirements')->nullable()->after('deadline');
        });
    }

    public function down(): void
    {
        Schema::table('gigs', function (Blueprint $table): void {
            $table->dropColumn([
                'proposed_price',
                'currency',
                'deadline',
                'special_requirements',
            ]);
        });
    }
};
