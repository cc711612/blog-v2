<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // 前面有 Cloudflare → 主機 nginx → 容器 nginx，信任內網 proxy 的 X-Forwarded-*，
        // 讓 url()/route() 產生 https 網址（canonical、og:url、轉址）
        $middleware->trustProxies(at: ['127.0.0.1', '172.16.0.0/12']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
