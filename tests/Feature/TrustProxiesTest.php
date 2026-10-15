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
            ->assertSee('<meta property="og:url" content="https://localhost/article"', false)
            ->assertSee('<link rel="canonical" href="https://localhost/article"', false);
    }

    public function test_forwarded_headers_from_untrusted_ip_are_ignored(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('http://localhost/article')
            ->assertOk()
            ->assertSee('<meta property="og:url" content="http://localhost/article"', false);
    }
}
