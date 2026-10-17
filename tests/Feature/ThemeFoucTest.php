<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeFoucTest extends TestCase
{
    use RefreshDatabase;

    public function test_theme_is_applied_in_head_before_any_stylesheet(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $headEnd = strpos($html, '</head>');
        $themeInHead = strpos($html, "localStorage.getItem('theme')");
        $firstStyle = strpos($html, '<style>');

        $this->assertNotFalse($themeInHead);
        $this->assertLessThan($headEnd, $themeInHead, 'theme script must be inside <head>');
        $this->assertLessThan($firstStyle, $themeInHead, 'theme script must run before the first <style>');
    }

    public function test_toggle_button_binding_is_null_safe(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('if (btn) {', $html);
        $this->assertStringNotContainsString("\n        btn.textContent", $html);
    }
}
