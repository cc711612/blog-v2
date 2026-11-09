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

    /**
     * k3s：Traefik（Pod 網段 10.42.0.0/16）把 cloudflared 帶來的 X-Forwarded-Proto 轉給 Pod 內 nginx；
     * Service 網段 10.43.0.0/16 也要受信。舊的 docker 內網（172.16.0.0/12）維持受信，usongrat 部署不受影響。
     */
    public static function k3sProxyPeers(): array
    {
        return [
            'traefik pod (10.42.0.0/16)' => ['10.42.0.15'],
            'pod cidr upper bound' => ['10.42.255.254'],
            'service cidr (10.43.0.0/16)' => ['10.43.12.34'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('k3sProxyPeers')]
    public function test_forwarded_https_from_k3s_proxy_generates_https_urls(string $peer): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => $peer])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('http://localhost/article')
            ->assertOk()
            ->assertSee('<meta property="og:url" content="https://localhost/"', false)
            ->assertSee('<link rel="canonical" href="https://localhost/"', false);
    }

    public static function untrustedNeighbourPeers(): array
    {
        // 緊鄰 k3s 網段、但不在受信清單的位址：不可因為放寬 10.42/10.43 而誤信整個 10.0.0.0/8
        return [
            'below pod cidr' => ['10.41.255.255'],
            'above service cidr' => ['10.44.0.1'],
            'usongrat private lan' => ['10.0.0.66'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('untrustedNeighbourPeers')]
    public function test_forwarded_https_from_neighbouring_10_x_is_ignored(string $peer): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => $peer])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('http://localhost/article')
            ->assertOk()
            ->assertSee('<meta property="og:url" content="http://localhost/"', false);
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
