<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $userColumns = array_flip(Schema::getColumnListing('users'));
        $commentColumns = array_flip(Schema::getColumnListing('comments'));
        $socialColumns = array_flip(Schema::getColumnListing('socials'));

        DB::table('users')->updateOrInsert(
            ['id' => 32],
            array_intersect_key([
                'name' => 'Roy',
                'email' => 'roy@example.com',
                'password' => Hash::make('password'),
                'api_token' => 'legacy-token-32',
                'introduction' => 'Legacy account used for migration and sitemap baseline.',
                'created_at' => $now,
                'updated_at' => $now,
            ], $userColumns)
        );

        $this->call(ImportUser32ArticlesSeeder::class);

        $articleIds = DB::table('articles')
            ->where('user_id', 32)
            ->orderByDesc('id')
            ->limit(2)
            ->pluck('id')
            ->values();

        if ($articleIds->isNotEmpty()) {
            $commentRows = [
                array_intersect_key([
                    'id' => 1,
                    'article_id' => (int) $articleIds[0],
                    'user_id' => 32,
                    'guest_name' => null,
                    'content' => 'First MVP comment.',
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $commentColumns),
                array_intersect_key([
                    'id' => 2,
                    'article_id' => (int) ($articleIds[1] ?? $articleIds[0]),
                    'user_id' => null,
                    'guest_name' => 'Guest User',
                    'content' => 'This article helps a lot.',
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $commentColumns),
            ];

            $commentUpdateColumns = array_values(array_filter(
                ['article_id', 'guest_name', 'content', 'status', 'updated_at'],
                fn (string $column) => isset($commentColumns[$column])
            ));

            DB::table('comments')->upsert($commentRows, ['id'], $commentUpdateColumns);
        }

        DB::table('socials')->updateOrInsert(
            ['social_type' => 3, 'social_type_value' => 'google-roy-32'],
            array_intersect_key([
                'name' => 'Roy',
                'email' => 'roy@example.com',
                'image' => null,
                'followed' => false,
                'token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ], $socialColumns)
        );

        $socialId = DB::table('socials')
            ->where('social_type', 3)
            ->where('social_type_value', 'google-roy-32')
            ->value('id');

        if ($socialId !== null) {
            DB::table('user_social')->updateOrInsert([
                'user_id' => 32,
                'social_id' => $socialId,
            ]);
        }
    }
}
