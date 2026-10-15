<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CodeToolbarTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_page_ships_code_toolbar_with_language_label_and_copy_button(): void
    {
        DB::table('users')->insert([
            'id' => 32,
            'name' => 'Roy',
            'email' => 'roy@example.com',
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('articles')->insert([
            'id' => 80,
            'user_id' => 32,
            'title' => 'Code article',
            'slug' => 'code-article',
            'content' => '<pre><code class="language-bash">php artisan migrate</code></pre>',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $html = $this->followingRedirects()->get('/article/80')->assertOk()->getContent();

        $this->assertStringContainsString('<code class="language-bash">php artisan migrate</code>', $html);
        // 工具列樣式沿用主題變數，深淺色模式都會跟著切換
        $this->assertMatchesRegularExpression('/\.article-content \.code-toolbar \{[^}]*var\(--code-border\)/', $html);
        // 每個 pre 在語法高亮後包進 .code-block，顯示語言並提供複製按鈕
        $this->assertStringContainsString('addCodeToolbars();', $html);
        $this->assertStringContainsString("button.textContent = '複製';", $html);
        $this->assertStringContainsString("button.textContent = '已複製';", $html);
        $this->assertStringContainsString('navigator.clipboard.writeText(text)', $html);
        $this->assertStringContainsString("bash: 'Bash'", $html);
        $this->assertStringContainsString('highlight.js/11.11.1/languages/nginx.min.js', $html);
    }
}
