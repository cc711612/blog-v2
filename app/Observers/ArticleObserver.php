<?php

namespace App\Observers;

use App\Jobs\PurgeCloudflareCacheJob;
use App\Models\Article;
use App\Modules\Content\Application\Services\ArticleCacheUrls;
use Illuminate\Support\Facades\DB;

/**
 * 文章異動後清 Cloudflare 快取。
 * 網址在事件當下計算（此時還拿得到舊 slug/title），實際呼叫 API 排在 DB commit 之後、回應送出之後。
 */
class ArticleObserver
{
    public function __construct(private readonly ArticleCacheUrls $urls) {}

    public function saved(Article $article): void
    {
        // 新建時 original 為空 → null；更新時為存檔前的值（saved 事件結束後才會 syncOriginal）
        $previousSlug = $article->getOriginal('slug');
        $previousTitle = $article->getOriginal('title');

        $this->purge($this->urls->forArticleWithShared(
            $article,
            $previousSlug === null ? null : (string) $previousSlug,
            $previousTitle === null ? null : (string) $previousTitle,
        ));
    }

    public function deleted(Article $article): void
    {
        $this->purge($this->urls->forArticleWithShared($article));
    }

    public function restored(Article $article): void
    {
        $this->purge($this->urls->forArticleWithShared($article));
    }

    /**
     * @param  array<int, string>  $urls
     */
    private function purge(array $urls): void
    {
        DB::afterCommit(fn () => PurgeCloudflareCacheJob::dispatchAfterResponse($urls));
    }
}
