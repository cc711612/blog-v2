<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaManifestTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_uses_brand_and_has_maskable_icons(): void
    {
        $m = $this->get('/manifest.json')->assertOk()->json();

        $this->assertSame(config('seo.site_name'), $m['name']);
        $this->assertSame('zh-Hant', $m['lang']);
        $this->assertSame('#111827', $m['theme_color']);
        $purposes = array_column($m['icons'], 'purpose');
        $this->assertContains('maskable', $purposes);
        $this->assertContains('any', $purposes);
    }

    public function test_every_manifest_icon_exists_with_declared_size(): void
    {
        $m = $this->get('/manifest.json')->json();

        foreach ($m['icons'] as $icon) {
            $path = public_path(ltrim(strtok($icon['src'], '?'), '/'));
            $this->assertFileExists($path);
            [$w, $h] = getimagesize($path);
            $this->assertSame($icon['sizes'], $w.'x'.$h, $icon['src']);
        }
    }

    public function test_layout_links_favicon_and_brand_theme_color(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('<meta name="theme-color" content="#111827">', $html);
        $this->assertStringContainsString('/favicon.ico', $html);
        $this->assertGreaterThan(0, filesize(public_path('favicon.ico')));
    }
}
