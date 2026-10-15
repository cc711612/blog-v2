<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use ReflectionProperty;
use Tests\TestCase;

class LivewireCsrfExceptionTest extends TestCase
{
    /**
     * 文章頁由 Cloudflare 邊緣快取，Livewire 的 /livewire/update 必須排除 CSRF 檢查，
     * 否則拿到快取 HTML（別人的 token）的訪客操作留言區會 419。
     * （單元測試環境 Laravel 會整體略過 CSRF，所以直接檢查例外清單。）
     */
    public function test_livewire_update_is_excluded_from_csrf_verification(): void
    {
        $prop = new ReflectionProperty(VerifyCsrfToken::class, 'neverVerify');
        $this->assertContains('livewire/update', $prop->getValue());
    }
}
