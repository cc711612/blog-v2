<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SeoFallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'https://roy.example.com']);

        DB::table('users')->insert([
            'id' => 32, 'name' => 'Roy', 'email' => 'roy@example.com', 'password' => 'x',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function insertArticle(int $id, ?array $seo, string $createdAt, string $updatedAt): void
    {
        DB::table('articles')->insert([
            'id' => $id, 'user_id' => 32, 'title' => 'Post '.$id, 'slug' => 'post-'.$id,
            'content' => '<p>這是第 '.$id.' 篇內文摘要</p>', 'status' => 1,
            'seo' => $seo === null ? null : json_encode($seo, JSON_UNESCAPED_UNICODE),
            'created_at' => $createdAt, 'updated_at' => $updatedAt,
        ]);
    }

    public function test_empty_seo_description_and_keywords_fall_back_to_defaults(): void
    {
        $this->insertArticle(1, ['description' => '', 'keyword' => ''], '2026-01-01 00:00:00', '2026-01-01 00:00:00');

        $html = $this->get('/article/1/post-1')->assertOk()->getContent();

        $this->assertStringNotContainsString('<meta name="description" content="">', $html);
        $this->assertMatchesRegularExpression('#<meta name="description" content="[^"]*這是第 1 篇內文摘要#u', $html);
        $this->assertMatchesRegularExpression('#<meta name="keywords" content="blog,#', $html);
    }

    public function test_sitemap_home_lastmod_uses_newest_updated_at_not_highest_id(): void
    {
        // id 較小的文章 updated_at 反而較新（改舊文）
        $this->insertArticle(1, null, '2026-01-01 00:00:00', '2026-09-09 09:09:09');
        $this->insertArticle(2, null, '2026-02-01 00:00:00', '2026-03-03 03:03:03');

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '#<loc>https://roy\.example\.com/</loc>\s*<lastmod>2026-09-09T09:09:09[^<]*</lastmod>#',
            $xml
        );
    }

    public function test_feed_last_build_date_uses_newest_updated_at_not_highest_id(): void
    {
        $this->insertArticle(1, null, '2026-01-01 00:00:00', '2026-09-09 09:09:09');
        $this->insertArticle(2, null, '2026-02-01 00:00:00', '2026-03-03 03:03:03');

        $xml = $this->get('/feed.xml')->assertOk()->getContent();

        $this->assertStringContainsString('<lastBuildDate>Wed, 09 Sep 2026 09:09:09 +0000</lastBuildDate>', $xml);
    }
}
