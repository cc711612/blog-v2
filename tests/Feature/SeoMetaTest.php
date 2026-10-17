<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeoMetaTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'https://roy.example.com';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => self::BASE]);

        DB::table('users')->insert([
            'id' => 32,
            'name' => 'Roy',
            'email' => 'roy@example.com',
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertArticle(int $id, string $title, int $status = 1, ?array $seo = null): void
    {
        DB::table('articles')->insert([
            'id' => $id,
            'user_id' => 32,
            'title' => $title,
            'slug' => 'post-'.$id,
            'content' => '<p>內文 '.$id.'</p>',
            'status' => $status,
            'seo' => $seo === null ? null : json_encode($seo, JSON_UNESCAPED_UNICODE),
            'created_at' => '2026-01-02 03:04:05',
            'updated_at' => '2026-02-03 04:05:06',
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function jsonLdBlocks(string $html): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);

        return array_map(fn (string $raw) => json_decode($raw, true, 512, JSON_THROW_ON_ERROR), $m[1]);
    }

    public function test_home_has_branded_title_and_description_and_website_json_ld(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('<title>Roy 的技術筆記｜Laravel、PHP 與 DevOps 實戰</title>', $html);
        $this->assertStringContainsString('<meta name="description" content="後端工程師 Roy 的技術部落格：Laravel、PHP、MySQL、Redis、Docker 與自架 VM 維運（監控、CI/CD、資安）的實戰筆記與踩坑紀錄。">', $html);
        $this->assertStringContainsString('<link rel="alternate" type="application/rss+xml" title="Roy 的技術筆記" href="', $html);

        $blocks = $this->jsonLdBlocks($html);
        $this->assertCount(1, $blocks);
        $this->assertSame('WebSite', $blocks[0]['@type']);
        $this->assertSame(self::BASE.'/', $blocks[0]['url']);
        $this->assertSame('Person', $blocks[0]['publisher']['@type']);
    }

    public function test_search_and_paged_titles_include_site_name_and_differ_from_home(): void
    {
        $search = $this->get('/?search=docker')->assertOk()->getContent();
        $this->assertStringContainsString('<title>搜尋：docker｜Roy 的技術筆記</title>', $search);
        $this->assertSame([], $this->jsonLdBlocks($search));

        // 第 2 頁要真的存在（超出最後一頁會回 404），需要超過 10 篇
        foreach (range(1, 11) as $id) {
            $this->insertArticle($id, 'Paged '.$id);
        }
        $paged = $this->get('/?page=2')->assertOk()->getContent();
        $this->assertStringContainsString('<title>文章列表 第 2 頁｜Roy 的技術筆記</title>', $paged);
        $this->assertStringNotContainsString('Laravel、PHP 與 DevOps 實戰', $paged);
    }

    public function test_home_og_image_defaults_and_twitter_image(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('<meta property="og:image" content="'.self::BASE.'/images/og-default.png">', $html);
        $this->assertStringContainsString('<meta name="twitter:image" content="'.self::BASE.'/images/og-default.png">', $html);
        $this->assertStringContainsString('<meta property="og:image:width" content="1200">', $html);
        $this->assertStringContainsString('<meta property="og:image:height" content="630">', $html);
        $this->assertStringContainsString('<meta property="og:image:alt"', $html);
    }

    public function test_article_page_has_json_ld_published_time_and_og_image(): void
    {
        $this->insertArticle(80, 'Laravel 佇列實戰');

        $html = $this->get('/article/80/post-80')->assertOk()->getContent();

        $blocks = $this->jsonLdBlocks($html);
        $this->assertCount(2, $blocks);
        $types = array_column($blocks, '@type');
        $this->assertSame(['BlogPosting', 'BreadcrumbList'], $types);

        $post = $blocks[0];
        $this->assertSame('Laravel 佇列實戰', $post['headline']);
        $this->assertSame('2026-01-02T03:04:05+00:00', $post['datePublished']);
        $this->assertSame('2026-02-03T04:05:06+00:00', $post['dateModified']);
        $this->assertSame('zh-Hant', $post['inLanguage']);
        $this->assertSame('Roy', $post['author']['name']);
        $this->assertSame(self::BASE.'/', $post['author']['url']);
        $this->assertSame(self::BASE.'/images/og-default.png', $post['image']);
        $this->assertStringEndsWith('/article/80/post-80', $post['mainEntityOfPage']);

        $items = $blocks[1]['itemListElement'];
        $this->assertCount(3, $items);
        $this->assertSame([1, 2, 3], array_column($items, 'position'));
        $this->assertSame('Laravel 佇列實戰', $items[2]['name']);

        $this->assertStringContainsString('<meta property="article:published_time" content="2026-01-02T03:04:05+00:00">', $html);
        $this->assertStringContainsString('<meta property="article:modified_time" content="2026-02-03T04:05:06+00:00">', $html);
        $this->assertStringContainsString('<meta property="og:image" content="'.self::BASE.'/images/og-default.png">', $html);
        $this->assertStringContainsString('<meta name="twitter:image" content="'.self::BASE.'/images/og-default.png">', $html);
        $this->assertStringContainsString('<meta property="og:image:width" content="1200">', $html);
    }

    public function test_article_seo_image_takes_precedence(): void
    {
        $this->insertArticle(81, '自訂圖', 1, ['image' => 'https://cdn.example.com/a.png']);

        $html = $this->get('/article/81/post-81')->assertOk()->getContent();

        $this->assertStringContainsString('<meta property="og:image" content="https://cdn.example.com/a.png">', $html);
        $this->assertStringContainsString('<meta name="twitter:image" content="https://cdn.example.com/a.png">', $html);
        $this->assertStringNotContainsString('og-default.png', $html);
    }

    public function test_json_ld_escapes_script_breakout_in_title(): void
    {
        $title = 'A </script><script>alert(1)</script> & B';
        $this->insertArticle(82, $title);

        $html = $this->get('/article/82/post-82')->assertOk()->getContent();

        $this->assertSame(0, substr_count($html, '<script>alert(1)'));
        $blocks = $this->jsonLdBlocks($html);
        $this->assertSame($title, $blocks[0]['headline']);
    }

    public function test_feed_returns_valid_rss_with_only_published_articles(): void
    {
        $this->insertArticle(90, 'Published one', 1, ['description' => '自訂摘要']);
        $this->insertArticle(91, 'Draft one', 0);
        $this->insertArticle(92, 'Published two', 1);

        $response = $this->get('/feed.xml')->assertOk();
        $this->assertStringContainsString('application/rss+xml', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('charset=UTF-8', $response->headers->get('Content-Type'));

        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml);
        $this->assertSame('2.0', (string) $xml['version']);
        $this->assertSame('Roy 的技術筆記', (string) $xml->channel->title);
        $this->assertSame(self::BASE.'/', (string) $xml->channel->link);
        $this->assertSame('zh-TW', (string) $xml->channel->language);

        $atom = $xml->channel->children('http://www.w3.org/2005/Atom')->link;
        $this->assertSame('self', (string) $atom->attributes()['rel']);
        $this->assertSame(self::BASE.'/feed.xml', (string) $atom->attributes()['href']);

        $titles = [];
        foreach ($xml->channel->item as $item) {
            $titles[] = (string) $item->title;
        }
        $this->assertSame(['Published two', 'Published one'], $titles);

        $first = $xml->channel->item[1];
        $this->assertSame(self::BASE.'/article/90/post-90', (string) $first->link);
        $this->assertSame((string) $first->link, (string) $first->guid);
        $this->assertSame('true', (string) $first->guid['isPermaLink']);
        $this->assertSame('Fri, 02 Jan 2026 03:04:05 +0000', (string) $first->pubDate);
        $this->assertSame('自訂摘要', (string) $first->description);
        $this->assertSame('內文 92', (string) $xml->channel->item[0]->description);
    }

    public function test_feed_limits_to_20_items(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            $this->insertArticle(100 + $i, 'Post '.$i);
        }

        $xml = simplexml_load_string($this->get('/feed.xml')->getContent());

        $this->assertCount(20, $xml->channel->item);
    }

    public function test_feed_escapes_xml_special_characters(): void
    {
        $title = 'Tom & Jerry </script> <b>"x"</b>';
        $this->insertArticle(120, $title, 1, ['description' => 'a & b <c>']);

        $xml = simplexml_load_string($this->get('/feed.xml')->assertOk()->getContent());

        $this->assertNotFalse($xml);
        $this->assertSame($title, (string) $xml->channel->item[0]->title);
        $this->assertSame('a & b <c>', (string) $xml->channel->item[0]->description);
    }

    public function test_home_and_article_index_canonical_point_to_home(): void
    {
        foreach (range(1, 11) as $id) {
            $this->insertArticle($id, 'A'.$id);
        }
        $this->get('/')->assertOk()->assertSee('<link rel="canonical" href="'.rtrim(route('home'), '/').'/"', false);
        $this->get('/article')->assertOk()->assertSee('<link rel="canonical" href="'.rtrim(route('home'), '/').'/"', false);
        $this->get('/article?page=2')->assertSee('page=2', false);
    }

    public function test_sitemap_no_longer_lists_article_index(): void
    {
        $this->insertArticle(1, 'A');
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertStringNotContainsString('<loc>'.self::BASE.'/article</loc>', $xml);
        $this->assertStringContainsString('<loc>'.self::BASE.'/</loc>', $xml);
    }

    public function test_feed_limits_rows_in_query(): void
    {
        for ($i = 1; $i <= 25; $i++) { $this->insertArticle($i, 'T'.$i); }
        \Illuminate\Support\Facades\DB::enableQueryLog();
        $xml = $this->get('/feed.xml')->assertOk()->getContent();
        $q = collect(\Illuminate\Support\Facades\DB::getQueryLog())->pluck('query')->first(fn ($sql) => str_contains($sql, 'from "articles"'));
        $this->assertNotNull($q);
        $limit = (int) config('seo.feed.limit');
        $this->assertStringContainsString('limit '.$limit, $q);
        $this->assertSame($limit, substr_count($xml, '<item>'));
    }

    public function test_custom_og_image_dimensions_are_output_when_provided(): void
    {
        $this->insertArticle(9, 'Custom', 1, ['image' => self::BASE.'/images/og/9.jpg', 'image_width' => 1200, 'image_height' => 630]);
        $this->get('/article/9/post-9')->assertOk()
            ->assertSee('<meta property="og:image" content="'.self::BASE.'/images/og/9.jpg"', false)
            ->assertSee('<meta property="og:image:width" content="1200"', false)
            ->assertSee('<meta property="og:image:height" content="630"', false);
    }
}
