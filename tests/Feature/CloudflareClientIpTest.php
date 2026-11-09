<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CloudflareClientIpTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/_test/ip', fn () => response(request()->ip()));
    }

    private function ipFor(string $peer, array $headers): string
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $peer])
            ->withHeaders($headers)
            ->get('/_test/ip')
            ->assertOk()
            ->getContent();
    }

    public function test_cf_connecting_ip_is_used_when_request_came_through_cloudflare(): void
    {
        // 172.67.0.5 在 Cloudflare 範圍（172.64.0.0/13）；最後的 172.18.0.1 是主機 nginx 的內網 IP
        $ip = $this->ipFor('172.21.0.5', [
            'X-Forwarded-For' => '203.0.113.50, 172.67.0.5, 172.18.0.1',
            'CF-Connecting-IP' => '203.0.113.50',
        ]);

        $this->assertSame('203.0.113.50', $ip);
    }

    public function test_cf_connecting_ip_is_ignored_when_forwarded_chain_has_no_cloudflare_hop(): void
    {
        // 直連 origin 的攻擊者：XFF 最後一跳是他自己的 IP，CF-Connecting-IP 是偽造的
        $ip = $this->ipFor('172.21.0.5', [
            'X-Forwarded-For' => '198.51.100.9',
            'CF-Connecting-IP' => '1.2.3.4',
        ]);

        $this->assertSame('198.51.100.9', $ip);
    }

    public function test_cf_connecting_ip_is_ignored_from_untrusted_peer(): void
    {
        $ip = $this->ipFor('198.51.100.20', [
            'X-Forwarded-For' => '172.67.0.5',
            'CF-Connecting-IP' => '1.2.3.4',
        ]);

        $this->assertSame('198.51.100.20', $ip);
    }

    public function test_internal_proxies_keep_docker_ranges_and_add_k3s_ranges(): void
    {
        $proxies = \App\Http\Middleware\ResolveCloudflareClientIp::INTERNAL_PROXIES;

        foreach (['127.0.0.1', '::1', '172.16.0.0/12', '10.42.0.0/16', '10.43.0.0/16'] as $range) {
            $this->assertContains($range, $proxies);
        }
    }

    public function test_k3s_tunnel_chain_resolves_visitor_ip(): void
    {
        // Cloudflare → cloudflared（10.42.0.9）→ Traefik（REMOTE_ADDR 10.42.0.15）：
        // 邊緣 IP 不在 XFF 裡，訪客 IP 由 TrustProxies 從 XFF 取最右邊的非受信位址
        $ip = $this->ipFor('10.42.0.15', [
            'X-Forwarded-For' => '203.0.113.50, 10.42.0.9',
            'CF-Connecting-IP' => '203.0.113.50',
        ]);

        $this->assertSame('203.0.113.50', $ip);
    }

    public function test_k3s_tunnel_chain_ignores_client_supplied_forwarded_for(): void
    {
        // 訪客自己帶的 X-Forwarded-For 會被 Cloudflare 接在前面，不能被當成訪客 IP
        $ip = $this->ipFor('10.42.0.15', [
            'X-Forwarded-For' => '1.2.3.4, 203.0.113.50, 10.42.0.9',
            'CF-Connecting-IP' => '203.0.113.50',
        ]);

        $this->assertSame('203.0.113.50', $ip);
    }

    public function test_cf_connecting_ip_is_used_when_cloudflare_edge_reaches_k3s_proxy(): void
    {
        // 經 Cloudflare 邊緣（172.67.0.5）再進 k3s Pod 網段的 proxy：與 usongrat 鏈相同，採信 CF-Connecting-IP
        $ip = $this->ipFor('10.42.0.15', [
            'X-Forwarded-For' => '198.51.100.7, 172.67.0.5, 10.43.0.10',
            'CF-Connecting-IP' => '203.0.113.50',
        ]);

        $this->assertSame('203.0.113.50', $ip);
    }

    public function test_cf_connecting_ip_is_ignored_from_peer_outside_k3s_ranges(): void
    {
        $ip = $this->ipFor('10.44.0.1', [
            'X-Forwarded-For' => '172.67.0.5',
            'CF-Connecting-IP' => '1.2.3.4',
        ]);

        $this->assertSame('10.44.0.1', $ip);
    }

    public function test_invalid_cf_connecting_ip_is_ignored(): void
    {
        $ip = $this->ipFor('172.21.0.5', [
            'X-Forwarded-For' => '203.0.113.50, 172.67.0.5',
            'CF-Connecting-IP' => 'not-an-ip',
        ]);

        $this->assertSame('172.67.0.5', $ip);
    }
}
