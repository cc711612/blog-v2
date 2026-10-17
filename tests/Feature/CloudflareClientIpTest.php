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

    public function test_invalid_cf_connecting_ip_is_ignored(): void
    {
        $ip = $this->ipFor('172.21.0.5', [
            'X-Forwarded-For' => '203.0.113.50, 172.67.0.5',
            'CF-Connecting-IP' => 'not-an-ip',
        ]);

        $this->assertSame('172.67.0.5', $ip);
    }
}
