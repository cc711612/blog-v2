<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * 本機開發用的範例資料。只「補不存在的資料」，絕不覆寫既有 row（尤其是 user 32 的密碼與 token），
     * 且禁止在 production 執行。
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new \RuntimeException('DatabaseSeeder is for local development only; refusing to run in production.');
        }

        $now = now();
        $userColumns = array_flip(Schema::getColumnListing('users'));
        $commentColumns = array_flip(Schema::getColumnListing('comments'));
        $socialColumns = array_flip(Schema::getColumnListing('socials'));

        // insertOrIgnore：user 32 已存在就完全不動；新建時用隨機密碼與 token，不留可猜的預設憑證
        DB::table('users')->insertOrIgnore(
            array_intersect_key([
                'id' => 32,
                'name' => 'Roy',
                'email' => 'roy@example.com',
                'password' => Hash::make(Str::random(40)),
                'api_token' => Str::random(60),
                'introduction' => 'Legacy account used for migration and sitemap baseline.',
                'created_at' => $now,
                'updated_at' => $now,
            ], $userColumns)
        );

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

            // 只補不存在的 id，不覆寫既有留言
            DB::table('comments')->insertOrIgnore($commentRows);
        }

        $socialExists = DB::table('socials')
            ->where('social_type', 3)
            ->where('social_type_value', 'google-roy-32')
            ->exists();

        if (! $socialExists) {
            DB::table('socials')->insert(array_intersect_key([
                'social_type' => 3,
                'social_type_value' => 'google-roy-32',
                'name' => 'Roy',
                'email' => 'roy@example.com',
                'image' => null,
                'followed' => false,
                'token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ], $socialColumns));
        }

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
