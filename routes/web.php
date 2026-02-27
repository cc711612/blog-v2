<?php

use App\Http\Controllers\Web\ArticleController;
use App\Http\Controllers\Web\SitemapController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::get('/manifest.json', function (): JsonResponse {
    $manifest = [
        'name' => config('app.name', 'Roy-Try-Catch'),
        'short_name' => 'RoyBlog',
        'start_url' => '/',
        'scope' => '/',
        'display' => 'standalone',
        'background_color' => '#f6f7f9',
        'theme_color' => '#0f766e',
        'description' => 'Roy technical blog built with Laravel.',
        'icons' => [
            [
                'src' => '/images/icons/blog-icon-72x72.png',
                'sizes' => '72x72',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => '/images/icons/blog-icon-96x96.png',
                'sizes' => '96x96',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => '/images/icons/blog-icon-128x128.png',
                'sizes' => '128x128',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => '/images/icons/blog-icon-144x144.png',
                'sizes' => '144x144',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => '/images/icons/blog-icon-152x152.png',
                'sizes' => '152x152',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => '/images/icons/blog-icon-192x192.png',
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => '/images/icons/blog-icon-384x384.png',
                'sizes' => '384x384',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => '/images/icons/blog-icon-512x512.png',
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
        ],
        'shortcuts' => [
            [
                'name' => 'Articles',
                'description' => 'Browse articles',
                'url' => '/article',
                'icons' => [['src' => '/images/icons/blog-icon-72x72.png', 'sizes' => '72x72']],
            ],
        ],
    ];

    return response()
        ->json($manifest)
        ->header('Cache-Control', 'public, max-age=2592000');
})->name('manifest');

Route::get('/', [ArticleController::class, 'index'])->name('home');
Route::get('/article', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/article/{id}', [ArticleController::class, 'show'])->name('articles.show');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.xml');
