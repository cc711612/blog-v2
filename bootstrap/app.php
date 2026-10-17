<?php

use App\Http\Middleware\ResolveCloudflareClientIp;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // 前面有 Cloudflare → 主機 nginx → 容器 nginx，信任內網 proxy 的 X-Forwarded-For / -Proto，
        // 讓 url()/route() 產生 https 網址（canonical、og:url、轉址）。
        // ⚠️ 刻意不信任 X-Forwarded-Host / -Port：Laravel 預設會全信，攻擊者可偽造 Host，
        // 把 canonical、og:url 毒化進 Cloudflare 邊緣快取。見 tests/Feature/TrustProxiesTest.php。
        $middleware->trustProxies(
            at: ResolveCloudflareClientIp::INTERNAL_PROXIES,
            headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO,
        );

        // 經 Cloudflare 時還原真實訪客 IP（只在來源可信時採用 CF-Connecting-IP）
        $middleware->append(ResolveCloudflareClientIp::class);

        // 文章頁由 Cloudflare 邊緣快取（匿名訪客共用同一份 HTML），HTML 內的 Livewire CSRF token
        // 會是第一位訪客的，其他人在留言區搜尋／排序會 419。站上目前沒有任何登入，唯一會寫資料的
        // CommentPanel::submit 有 Auth::check() 擋著，CSRF 在這裡沒有保護對象；snapshot 防竄改由
        // APP_KEY checksum 負責。⚠️ 之後若加上登入，必須先移除這個例外並調整 Cloudflare 快取規則。
        $middleware->validateCsrfTokens(except: ['livewire/update']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
