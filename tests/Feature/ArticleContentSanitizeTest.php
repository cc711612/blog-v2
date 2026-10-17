<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ArticleContentSanitizeTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_page_strips_script_but_keeps_code_and_table(): void
    {
        DB::table('users')->insert([
            'id' => 32, 'name' => 'Roy', 'email' => 'roy@example.com', 'password' => 'x',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('articles')->insert([
            'id' => 9, 'user_id' => 32, 'title' => 'XSS', 'slug' => 'xss', 'status' => 1,
            'content' => '<p>safe text</p><script>window.pwned=1</script><img src="x" onerror="window.pwned=2">'
                .'<pre class="language-sh"><code class="hljs">echo "hi"</code></pre>'
                .'<table class="t"><tr><td>cell</td></tr></table>',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $html = $this->get('/article/9/xss')->assertOk()->getContent();

        $this->assertStringNotContainsString('window.pwned', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringContainsString('safe text', $html);
        $this->assertStringContainsString('<pre class="language-sh"><code class="hljs">', $html);
        $this->assertStringContainsString('<table class="t">', $html);
        $this->assertStringContainsString('cell', $html);
    }

    private function seedArticle(string $content): void
    {
        DB::table('users')->insert([
            'id' => 32, 'name' => 'Roy', 'email' => 'roy@example.com', 'password' => 'x',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('articles')->insert([
            'id' => 9, 'user_id' => 32, 'title' => 'Img', 'slug' => 'img', 'status' => 1, 'content' => $content,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_image_and_caption_styles_exist_without_allowing_style_attribute(): void
    {
        $this->seedArticle(
            '<p><img src="/a.png" alt="a" style="max-width:100%;" onerror="x()"></p>'
            .'<p style="text-align:center;color:#6b7280;font-size:14px;margin-top:-6px;">圖說</p>'
            .'<p style="color:red">other</p>'
        );

        $html = $this->get('/article/9/img')->assertOk()->getContent();

        // CSS 補回 sanitize 移除的 inline style 效果
        $this->assertStringContainsString('.article-content img { max-width: 100%; height: auto;', $html);
        $this->assertStringContainsString('.article-content .article-caption {', $html);
        // 已知圖說樣式轉成 class，其他 style 一律移除
        $this->assertStringContainsString('<p class="article-caption">圖說</p>', $html);
        $this->assertStringNotContainsString('style="max-width', $html);
        $this->assertStringNotContainsString('style="color:red"', $html);
    }

    public function test_list_service_content_html_is_sanitized(): void
    {
        $this->seedArticle('<p>ok</p><script>alert(1)</script>');

        $items = app(\App\Modules\Content\Application\Services\ListArticlesService::class)->handle(32, 1, 10)->items();

        $this->assertStringNotContainsString('<script', $items[0]['content_html']);
        $this->assertStringContainsString('<p>ok</p>', $items[0]['content_html']);
    }
}
