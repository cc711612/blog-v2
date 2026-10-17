<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Modules\Content\Application\Services\ArticleCacheUrls;
use App\Modules\Content\Infrastructure\Cloudflare\CloudflareCachePurger;
use Illuminate\Console\Command;

/**
 * 手動補清 Cloudflare 快取（維運直接用 DB::table 改文章時不會觸發 model 事件）。
 */
class PurgeArticleCacheCommand extends Command
{
    protected $signature = 'cloudflare:purge-article
        {id?* : 文章 ID，可多個}
        {--all-articles : 清除所有文章（含已軟刪除）}';

    protected $description = '手動清除指定文章（或全部文章）的 Cloudflare 快取';

    public function handle(ArticleCacheUrls $builder, CloudflareCachePurger $purger): int
    {
        $ids = array_map('intval', (array) $this->argument('id'));

        if (! $this->option('all-articles') && $ids === []) {
            $this->error('請指定文章 ID，或使用 --all-articles。');

            return self::FAILURE;
        }

        $query = Article::withTrashed()->orderBy('id');
        if (! $this->option('all-articles')) {
            $query->whereIn('id', $ids);
        }
        $articles = $query->get();

        if (! $this->option('all-articles')) {
            foreach (array_diff($ids, $articles->pluck('id')->map(fn ($i) => (int) $i)->all()) as $missing) {
                $this->warn("找不到文章 {$missing}，略過其專屬網址（仍會清共用網址）。");
            }
        }

        $urls = $builder->shared();
        foreach ($articles as $article) {
            $urls = [...$urls, ...$builder->forArticle($article)];
        }
        $urls = $builder->unique($urls);

        if (! $purger->isEnabled()) {
            $this->error('Cloudflare purge 未啟用或缺少設定（CLOUDFLARE_PURGE_ENABLED / CLOUDFLARE_ZONE_ID / CLOUDFLARE_API_TOKEN），未呼叫 API。');
            $this->line('預計清除的 URL：');
            foreach ($urls as $url) {
                $this->line("  {$url}");
            }

            return self::FAILURE;
        }

        $this->info('清除 URL（共 '.count($urls).' 筆）：');
        foreach ($urls as $url) {
            $this->line("  {$url}");
        }

        $failed = false;
        foreach ($purger->purge($urls) as $i => $result) {
            $n = $i + 1;
            $status = $result['status'] ?? 'n/a';
            $line = "批次 {$n}（".count($result['urls'])." 筆）：HTTP {$status} {$result['message']}";
            $this->line($line, $result['ok'] ? 'info' : 'error');
            $failed = $failed || ! $result['ok'];
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
