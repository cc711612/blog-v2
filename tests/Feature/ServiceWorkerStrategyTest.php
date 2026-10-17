<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * sw.js 沒有 JS 測試基礎設施，這裡以字串斷言固定幾個不能退化的策略；
 * 行為面請用瀏覽器 DevTools（Application → Cache Storage）手動確認，見 PR 說明。
 */
class ServiceWorkerStrategyTest extends TestCase
{
    private function sw(): string
    {
        return (string) file_get_contents(public_path('sw.js'));
    }

    public function test_cache_name_is_bumped_so_old_permanent_caches_are_purged(): void
    {
        $this->assertStringContainsString("'roy-blog-v2-cache-v4'", $this->sw());
        $this->assertStringNotContainsString('cache-v3', $this->sw());
    }

    public function test_only_ok_responses_are_cached(): void
    {
        $this->assertStringContainsString('response.ok', $this->sw());
    }

    public function test_cross_origin_requests_bypass_the_service_worker(): void
    {
        $this->assertStringContainsString('url.origin !== self.location.origin', $this->sw());
    }

    public function test_feed_and_sitemap_are_not_cached_and_manifest_is_network_first(): void
    {
        $sw = $this->sw();

        $this->assertStringNotContainsString('feed.xml', str_replace('/feed.xml、/sitemap.xml', '', $sw));
        $this->assertStringNotContainsString("'/sitemap.xml'", $sw);
        $this->assertStringContainsString("url.pathname === '/manifest.json'", $sw);
        // 靜態資源 cache-first 只限 /images/ 與 favicon
        $this->assertStringContainsString("STATIC_PREFIXES = ['/images/']", $sw);
    }

    public function test_offline_image_requests_do_not_fall_back_to_home_html(): void
    {
        // 舊版對所有請求失敗時 fallback 到 caches.match('/')；靜態資源分支不可再有這個行為
        $staticBranch = substr($this->sw(), strpos($this->sw(), '// 靜態資源'));
        $staticBranch = substr($staticBranch, 0, strpos($staticBranch, '// 其餘'));
        $this->assertStringNotContainsString("caches.match('/')", $staticBranch);
    }

    public function test_cached_documents_are_capped(): void
    {
        $this->assertStringContainsString('MAX_DOCUMENT_ENTRIES', $this->sw());
        $this->assertStringContainsString('cache.delete', $this->sw());
    }
}
