<?php

use App\Http\Controllers\Web\ArticleController;
use App\Http\Controllers\Web\FeedController;
use App\Http\Controllers\Web\SitemapController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::get('/manifest.json', function (): JsonResponse {
    $manifest = [
        'name' => config('seo.site_name', 'Roy 的技術筆記'),
        'short_name' => 'Roy 筆記',
        'lang' => 'zh-Hant',
        'start_url' => '/',
        'scope' => '/',
        'display' => 'standalone',
        'background_color' => '#0f172a',
        'theme_color' => '#111827',
        'description' => (string) config('seo.home_description'),
        'icons' => [
            [
                'src' => '/images/icons/blog-icon-72x72.png?v=20261015',
                'sizes' => '72x72',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => '/images/icons/blog-icon-96x96.png?v=20261015',
                'sizes' => '96x96',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => '/images/icons/blog-icon-128x128.png?v=20261015',
                'sizes' => '128x128',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => '/images/icons/blog-icon-144x144.png?v=20261015',
                'sizes' => '144x144',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => '/images/icons/blog-icon-152x152.png?v=20261015',
                'sizes' => '152x152',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => '/images/icons/blog-icon-192x192.png?v=20261015',
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => '/images/icons/blog-icon-384x384.png?v=20261015',
                'sizes' => '384x384',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => '/images/icons/blog-icon-512x512.png?v=20261015',
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => '/images/icons/blog-icon-maskable-192x192.png?v=20261015',
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'maskable',
            ],
            [
                'src' => '/images/icons/blog-icon-maskable-512x512.png?v=20261015',
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'maskable',
            ],
        ],
        'shortcuts' => [
            [
                'name' => '文章列表',
                'description' => '瀏覽所有文章',
                'url' => '/article',
                'icons' => [['src' => '/images/icons/blog-icon-72x72.png?v=20261015', 'sizes' => '72x72']],
            ],
        ],
    ];

    return response()
        ->json($manifest)
        ->header('Cache-Control', 'public, max-age=2592000');
})->name('manifest');

Route::get('/', [ArticleController::class, 'index'])->name('home');
Route::get('/article', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/article/{id}/{slug?}', [ArticleController::class, 'show'])->whereNumber('id')->name('articles.show');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.xml');
Route::get('/feed.xml', [FeedController::class, 'index'])->name('feed.xml');
