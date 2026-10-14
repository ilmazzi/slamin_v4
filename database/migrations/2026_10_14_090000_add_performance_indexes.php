<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private array $indexes = [
        'events_user_id_index' => 'events (user_id)',
        'events_public_feed_index' => 'events (status, is_public, published_at DESC, created_at DESC) WHERE deleted_at IS NULL',
        'poems_public_feed_index' => 'poems (status, is_public, published_at DESC, created_at DESC) WHERE deleted_at IS NULL',
        'poems_featured_index' => 'poems (featured_rank) WHERE is_featured = true AND deleted_at IS NULL',
        'articles_public_feed_index' => 'articles (status, is_public, published_at DESC, created_at DESC) WHERE deleted_at IS NULL',
        'articles_featured_index' => 'articles (featured_rank) WHERE is_featured = true AND deleted_at IS NULL',
        'media_items_public_feed_index' => 'media_items (status, visibility, published_at DESC) WHERE deleted_at IS NULL',
        'audit_logs_user_id_created_at_index' => 'audit_logs (user_id, created_at DESC)',
        'event_participant_invites_user_id_status_index' => 'event_participant_invites (user_id, status)',
        'event_audience_invites_user_id_status_index' => 'event_audience_invites (user_id, status)',
        'group_members_user_id_status_index' => 'group_members (user_id, status)',
        'comments_parent_id_index' => 'comments (parent_id) WHERE parent_id IS NOT NULL',
        'gigs_open_index' => 'gigs (status, opened_at DESC, created_at DESC) WHERE deleted_at IS NULL',
    ];

    public function up(): void
    {
        foreach ($this->indexes as $name => $definition) {
            DB::statement("CREATE INDEX IF NOT EXISTS {$name} ON {$definition}");
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->indexes) as $name) {
            DB::statement("DROP INDEX IF EXISTS {$name}");
        }
    }
};
