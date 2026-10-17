<?php

namespace Tests\Unit\Support;

use App\Support\ArticleHtmlSanitizer;
use PHPUnit\Framework\TestCase;

class ArticleHtmlSanitizerTest extends TestCase
{
    public function test_removes_script_event_handlers_and_dangerous_urls(): void
    {
        $html = ArticleHtmlSanitizer::clean(
            '<p onclick="steal()">hi</p><script>alert(1)</script><iframe src="https://evil.example"></iframe>'
            .'<a href="javascript:alert(1)">x</a><img src="x" onerror="alert(1)"><form action="/x"><input name="a"></form><style>p{}</style>'
        );

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('<iframe', $html);
        $this->assertStringNotContainsString('<form', $html);
        $this->assertStringNotContainsString('<style', $html);
        $this->assertStringContainsString('<p>hi</p>', $html);
    }

    public function test_keeps_code_blocks_with_classes(): void
    {
        $html = ArticleHtmlSanitizer::clean(
            '<pre class="language-php"><code class="hljs language-php">&lt;?php echo 1 &amp;&amp; 2; ?&gt;'."\n".'$a;</code></pre>'
        );

        $this->assertStringContainsString('<pre class="language-php">', $html);
        $this->assertStringContainsString('<code class="hljs language-php">', $html);
        $this->assertStringContainsString('&lt;?php echo 1 &amp;&amp; 2; ?&gt;'."\n".'$a;', $html);
    }

    public function test_keeps_tables_images_headings_lists_and_links(): void
    {
        $html = ArticleHtmlSanitizer::clean(
            '<h2 id="intro">Intro</h2><ul><li>a</li></ul><blockquote>q</blockquote>'
            .'<table class="tbl"><thead><tr><th>h</th></tr></thead><tbody><tr><td>1</td></tr></tbody></table>'
            .'<img src="/images/a.png" alt="alt" class="fig" width="10" height="20" loading="lazy">'
            .'<a href="https://example.com/x" target="_blank">ext</a><a href="/article/1">internal</a>'
        );

        foreach (['<h2 id="intro">', '<ul><li>a</li></ul>', '<blockquote>', '<table class="tbl">', '<thead>', '<tbody>', '<th>h</th>', '<td>1</td>'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
        $this->assertStringContainsString('<img src="/images/a.png" alt="alt" class="fig" width="10" height="20" loading="lazy"', $html);
        $this->assertStringContainsString('href="https://example.com/x"', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('noopener', $html);
        $this->assertStringContainsString('href="/article/1"', $html);
    }

    public function test_does_not_truncate_long_articles(): void
    {
        $paragraph = '<p>'.str_repeat('長文內容', 50).'</p>';
        $html = ArticleHtmlSanitizer::clean(str_repeat($paragraph, 400)); // 約 80k 字元，超過 Symfony 預設 20k 上限

        $this->assertSame(400, substr_count($html, '<p>'));
    }

    public function test_handles_null_and_empty(): void
    {
        $this->assertSame('', ArticleHtmlSanitizer::clean(null));
        $this->assertSame('', ArticleHtmlSanitizer::clean(''));
    }
}
