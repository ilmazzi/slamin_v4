<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gigs', function (Blueprint $table): void {
            $table->boolean('cachet_enabled')->default(false)->after('special_requirements');
            $table->boolean('travel_expenses_enabled')->default(false)->after('cachet_enabled');
            $table->decimal('travel_expenses_max', 10, 2)->nullable()->after('travel_expenses_enabled');
            $table->boolean('accommodation_enabled')->default(false)->after('travel_expenses_max');
            $table->text('accommodation_details')->nullable()->after('accommodation_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('gigs', function (Blueprint $table): void {
            $table->dropColumn([
                'cachet_enabled',
                'travel_expenses_enabled',
                'travel_expenses_max',
                'accommodation_enabled',
                'accommodation_details',
            ]);
        });
    }
};
