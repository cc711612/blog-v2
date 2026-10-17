<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NullTimestampTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('users')->insert([
            'id' => 32, 'name' => 'Roy', 'email' => 'roy@example.com', 'password' => 'x',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        // 舊資料：created_at / updated_at 皆為 NULL
        DB::table('articles')->insert([
            'id' => 5, 'user_id' => 32, 'title' => 'Legacy', 'slug' => 'legacy', 'content' => '<p>old</p>',
            'status' => 1, 'created_at' => null, 'updated_at' => null,
        ]);
        // 只有 created_at
        DB::table('articles')->insert([
            'id' => 6, 'user_id' => 32, 'title' => 'Half', 'slug' => 'half', 'content' => '<p>half</p>',
            'status' => 1, 'created_at' => '2026-01-02 03:04:05', 'updated_at' => null,
        ]);
        DB::table('comments')->insert([
            'id' => 1, 'article_id' => 5, 'user_id' => 32, 'content' => 'legacy comment',
            'status' => 1, 'created_at' => null, 'updated_at' => null,
        ]);
    }

    public function test_pages_do_not_500_when_timestamps_are_null(): void
    {
        $this->get('/')->assertOk()->assertSee('Legacy');
        $this->get('/article/5/legacy')->assertOk()->assertSee('legacy comment');
        $this->get('/article/6/half')->assertOk();
        $this->get('/sitemap.xml')->assertOk();
        $this->get('/feed.xml')->assertOk();
        $this->getJson('/api/articles')->assertOk();
        $this->getJson('/api/articles/5')->assertOk();
    }

    public function test_updated_at_falls_back_to_created_at(): void
    {
        $this->get('/article/6/half')
            ->assertOk()
            ->assertSee('2026-01-02 03:04:05');
    }
}
