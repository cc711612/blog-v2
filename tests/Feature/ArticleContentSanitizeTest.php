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
}
