<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HomeQueryCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_sidebar_does_not_run_full_listing_queries(): void
    {
        DB::table('users')->insert([
            'id' => 32, 'name' => 'Roy', 'email' => 'roy@example.com', 'password' => 'x',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach (range(1, 12) as $id) {
            DB::table('articles')->insert([
                'id' => $id, 'user_id' => 32, 'title' => 'Post '.$id, 'slug' => 'post-'.$id,
                'content' => '<p>內文 '.$id.'</p>', 'status' => 1,
                'created_at' => now()->subDays(20 - $id), 'updated_at' => now(),
            ]);
        }

        DB::enableQueryLog();
        $html = $this->get('/')->assertOk()->getContent();
        $count = count(DB::getQueryLog());

        // 改前 21 個查詢（2 次分頁 count/select/author + 10+5 次留言 COUNT）；改後 15 個
        $this->assertLessThanOrEqual(15, $count);

        // 側欄「最新文章」維持由新到舊的 5 篇
        preg_match('#<aside class="sidebar">.*?最新文章(.*?)</ul>#s', $html, $m);
        $this->assertSame(5, substr_count($m[1], '<li>'));
        $this->assertLessThan(strpos($m[1], 'Post 11'), strpos($m[1], 'Post 12'));
        $this->assertStringNotContainsString('Post 7', $m[1]);
    }
}
