<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, array<string, list<string>>>
     */
    private array $indexes = [
        'events' => [
            'events_user_id_index' => ['user_id'],
            'events_public_feed_index' => ['status', 'is_public', 'published_at', 'created_at'],
        ],
        'poems' => [
            'poems_public_feed_index' => ['status', 'is_public', 'published_at', 'created_at'],
            'poems_featured_index' => ['is_featured', 'featured_rank'],
        ],
        'articles' => [
            'articles_public_feed_index' => ['status', 'is_public', 'published_at', 'created_at'],
            'articles_featured_index' => ['is_featured', 'featured_rank'],
        ],
        'media_items' => [
            'media_items_public_feed_index' => ['status', 'visibility', 'published_at'],
        ],
        'audit_logs' => [
            'audit_logs_user_id_created_at_index' => ['user_id', 'created_at'],
        ],
        'event_participant_invites' => [
            'event_participant_invites_user_id_status_index' => ['user_id', 'status'],
        ],
        'event_audience_invites' => [
            'event_audience_invites_user_id_status_index' => ['user_id', 'status'],
        ],
        'group_members' => [
            'group_members_user_id_status_index' => ['user_id', 'status'],
        ],
        'comments' => [
            'comments_parent_id_index' => ['parent_id'],
        ],
        'gigs' => [
            'gigs_open_index' => ['status', 'opened_at', 'created_at'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            foreach ($indexes as $name => $columns) {
                if (Schema::hasIndex($table, $name) || $this->isCovered($table, $columns)) {
                    continue;
                }

                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
            }
        }
    }

    /**
     * @param  list<string>  $columns
     */
    private function isCovered(string $table, array $columns, ?string $except = null): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if ($index['name'] === $except) {
                continue;
            }

            if (array_slice($index['columns'], 0, count($columns)) === $columns) {
                return true;
            }
        }

        return false;
    }

    /**
     * MySQL drops a foreign key's implicit index once another index covers it,
     * so it has to be recreated before the covering index can be removed.
     */
    private function preserveForeignKeyIndexes(string $table, string $indexName): void
    {
        if (! in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        foreach (Schema::getForeignKeys($table) as $foreignKey) {
            $columns = $foreignKey['columns'];

            if ($this->isCovered($table, $columns, $indexName)) {
                continue;
            }

            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns));
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            foreach (array_keys($indexes) as $name) {
                if (! Schema::hasIndex($table, $name)) {
                    continue;
                }

                $this->preserveForeignKeyIndexes($table, $name);

                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
            }
        }
    }
};
