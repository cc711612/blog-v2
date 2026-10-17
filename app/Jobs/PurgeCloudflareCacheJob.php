<?php

namespace App\Jobs;

use App\Modules\Content\Infrastructure\Cloudflare\CloudflareCachePurger;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 清除 Cloudflare 快取。本站沒有 queue worker，因此以 dispatchAfterResponse 在回應送出後同進程執行。
 */
class PurgeCloudflareCacheJob
{
    use Dispatchable;

    /**
     * @param  array<int, string>  $urls
     */
    public function __construct(public readonly array $urls) {}

    public function handle(CloudflareCachePurger $purger): void
    {
        try {
            $purger->purge($this->urls);
        } catch (Throwable $e) {
            Log::warning('Cloudflare purge job error.', ['message' => get_class($e)]);
        }
    }
}
