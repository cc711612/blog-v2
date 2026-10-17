<?php

namespace App\Modules\Content\Infrastructure\Cloudflare;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 呼叫 Cloudflare purge_cache API（依 URL 清除）。
 * 永不丟例外：失敗只寫 warning log（不含 token），呼叫端（存檔流程）不受影響。
 */
class CloudflareCachePurger
{
    public function isEnabled(): bool
    {
        return (bool) config('cloudflare.purge.enabled')
            && filled(config('cloudflare.purge.zone_id'))
            && filled(config('cloudflare.purge.api_token'));
    }

    /**
     * @param  array<int, string>  $urls
     * @return array<int, array{urls: array<int, string>, ok: bool, status: int|null, message: string}>
     *                                                                                                  每個批次一筆結果；未啟用／缺設定時回傳空陣列
     */
    public function purge(array $urls): array
    {
        $urls = array_values(array_unique($urls));

        if ($urls === []) {
            return [];
        }

        if (! $this->isEnabled()) {
            Log::debug('Cloudflare purge skipped: disabled or missing zone/token config.', ['url_count' => count($urls)]);

            return [];
        }

        $results = [];
        $size = max(1, (int) config('cloudflare.purge.batch_size', 100));

        foreach (array_chunk($urls, $size) as $batch) {
            $results[] = $this->purgeBatch($batch);
        }

        return $results;
    }

    /**
     * @param  array<int, string>  $batch
     * @return array{urls: array<int, string>, ok: bool, status: int|null, message: string}
     */
    private function purgeBatch(array $batch): array
    {
        $endpoint = rtrim((string) config('cloudflare.purge.endpoint'), '/')
            .'/zones/'.config('cloudflare.purge.zone_id').'/purge_cache';

        try {
            $response = Http::withToken((string) config('cloudflare.purge.api_token'))
                ->acceptJson()
                ->timeout((int) config('cloudflare.purge.timeout', 5))
                ->retry((int) config('cloudflare.purge.retries', 2) + 1, 200, throw: false)
                ->post($endpoint, ['files' => $batch]);

            $ok = $response->successful() && $response->json('success') !== false;
            $message = $ok ? 'ok' : $this->errorSummary($response->json('errors'), $response->status());

            if (! $ok) {
                Log::warning('Cloudflare purge failed.', [
                    'status' => $response->status(),
                    'message' => $message,
                    'urls' => $batch,
                ]);
            }

            return ['urls' => $batch, 'ok' => $ok, 'status' => $response->status(), 'message' => $message];
        } catch (Throwable $e) {
            $message = $this->redact(get_class($e).': '.$e->getMessage());
            Log::warning('Cloudflare purge exception.', ['message' => $message, 'urls' => $batch]);

            return ['urls' => $batch, 'ok' => false, 'status' => null, 'message' => $message];
        }
    }

    private function errorSummary(mixed $errors, int $status): string
    {
        if (is_array($errors) && $errors !== []) {
            return $this->redact(collect($errors)
                ->map(fn ($e) => is_array($e) ? trim(($e['code'] ?? '').' '.($e['message'] ?? '')) : (string) $e)
                ->implode('; '));
        }

        return "HTTP {$status}";
    }

    private function redact(string $text): string
    {
        $token = (string) config('cloudflare.purge.api_token');

        return $token === '' ? $text : str_replace($token, '[REDACTED]', $text);
    }
}
