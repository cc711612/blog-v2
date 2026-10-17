<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
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

    /**
     * 護欄：CSRF 例外成立的前提是「全站沒有登入」。一旦出現登入相關路由，
     * livewire/update 就不能再排除 CSRF（否則登入後的留言等寫入可被跨站偽造），
     * 必須先移除例外並調整 Cloudflare 快取規則（見 bootstrap/app.php 註解）。
     */
    public function test_csrf_exception_is_not_kept_once_a_login_route_exists(): void
    {
        $loginRoutes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => $route->getName() === 'login'
                || preg_match('#(^|/)(login|register|logout|oauth|auth)(/|$)#i', $route->uri()) === 1)
            ->map(fn ($route) => $route->uri())
            ->values()
            ->all();

        $neverVerify = (new ReflectionProperty(VerifyCsrfToken::class, 'neverVerify'))->getValue();

        $this->assertFalse(
            $loginRoutes !== [] && in_array('livewire/update', $neverVerify, true),
            'Login routes exist ('.implode(', ', $loginRoutes).') but livewire/update is still excluded from CSRF verification. '
            .'Remove the exception in bootstrap/app.php and adjust the Cloudflare cache rules first.'
        );
    }
}
