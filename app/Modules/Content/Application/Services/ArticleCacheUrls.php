<?php

namespace App\Modules\Content\Application\Services;

use App\Models\Article;
use App\Support\ArticleSlug;

/**
 * 組出「文章異動後需要清 Cloudflare 快取」的絕對網址清單。
 */
class ArticleCacheUrls
{
    /**
     * 與文章列表無關、但會列出文章的共用網址（首頁、列表、sitemap、feed、API）。
     *
     * @return array<int, string>
     */
    public function shared(): array
    {
        return array_map(fn (string $path) => $this->absolute($path), [
            '/',
            '/article',
            '/sitemap.xml',
            '/feed.xml',
            '/api/articles',
        ]);
    }

    /**
     * 單篇文章自己的網址：canonical、舊 slug（若有）、API 單篇。
     *
     * @return array<int, string>
     */
    public function forArticle(Article $article, ?string $previousSlug = null, ?string $previousTitle = null): array
    {
        $id = (int) $article->id;
        $urls = [$this->articleUrl($id, ArticleSlug::from($article->slug, (string) $article->title, $id))];

        if ($previousSlug !== null || $previousTitle !== null) {
            $urls[] = $this->articleUrl($id, ArticleSlug::from($previousSlug, (string) ($previousTitle ?? $article->title), $id));
        }

        $urls[] = $this->absolute("/api/articles/{$id}");

        return $urls;
    }

    /**
     * 文章專屬網址 + 共用網址，去重。
     *
     * @return array<int, string>
     */
    public function forArticleWithShared(Article $article, ?string $previousSlug = null, ?string $previousTitle = null): array
    {
        return $this->unique([...$this->forArticle($article, $previousSlug, $previousTitle), ...$this->shared()]);
    }

    /**
     * @param  array<int, string>  $urls
     * @return array<int, string>
     */
    public function unique(array $urls): array
    {
        return array_values(array_unique($urls));
    }

    private function articleUrl(int $id, string $slug): string
    {
        return $this->absolute("/article/{$id}/{$slug}");
    }

    private function absolute(string $path): string
    {
        return rtrim((string) config('app.url'), '/').$path;
    }
}
