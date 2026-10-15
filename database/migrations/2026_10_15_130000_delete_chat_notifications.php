<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('notifications')
            ->whereIn('type', ['chat_message_received', 'chat_message_replied', 'chat_message_reacted'])
            ->delete();
    }

    public function down(): void {}
};
