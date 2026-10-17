<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrustProxiesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // 測試請求預設以 app.url 當 base；改成 http，才看得出 X-Forwarded-Proto 有沒有被採用
        config(['app.url' => 'http://localhost']);
    }

    public function test_forwarded_https_from_internal_proxy_generates_https_urls(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '172.21.0.5'])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('http://localhost/article')
            ->assertOk()
            ->assertSee('<meta property="og:url" content="https://localhost/"', false)
            ->assertSee('<link rel="canonical" href="https://localhost/"', false);
    }

    public function test_forwarded_headers_from_untrusted_ip_are_ignored(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('http://localhost/article')
            ->assertOk()
            ->assertSee('<meta property="og:url" content="http://localhost/"', false);
    }

    public function test_forwarded_host_from_internal_proxy_cannot_poison_canonical(): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => '172.21.0.5'])
            ->withHeaders([
                'X-Forwarded-Proto' => 'https',
                'X-Forwarded-Host' => 'evil.example.com',
                'X-Forwarded-Port' => '8443',
            ])
            ->get('http://localhost/article')
            ->assertOk();

        $html = $response->getContent();
        $this->assertStringNotContainsString('evil.example.com', $html);
        $this->assertStringContainsString('<link rel="canonical" href="https://localhost/"', $html);
        $this->assertStringContainsString('<meta property="og:url" content="https://localhost/"', $html);
    }

    public function test_forwarded_host_does_not_poison_article_canonical_or_redirect(): void
    {
        \Illuminate\Support\Facades\DB::table('users')->insert([
            'id' => 32, 'name' => 'Roy', 'email' => 'roy@example.com', 'password' => 'x',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\DB::table('articles')->insert([
            'id' => 7, 'user_id' => 32, 'title' => 'Hello', 'slug' => 'hello', 'content' => '<p>x</p>', 'status' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $headers = ['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'evil.example.com'];
        $server = ['REMOTE_ADDR' => '172.21.0.5'];

        $page = $this->withServerVariables($server)->withHeaders($headers)->get('http://localhost/article/7/hello')->assertOk();
        $this->assertStringNotContainsString('evil.example.com', $page->getContent());

        $redirect = $this->withServerVariables($server)->withHeaders($headers)->get('http://localhost/article/7/wrong-slug');
        $redirect->assertStatus(301);
        $this->assertStringNotContainsString('evil.example.com', (string) $redirect->headers->get('Location'));
    }
}
