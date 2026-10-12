<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $roles = [
            [
                'name' => 'moderator',
                'label' => 'Moderator',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'admin',
                'label' => 'Admin',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'translator',
                'label' => 'Translator',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['name' => $role['name']],
                $role
            );
        }
    }
}
