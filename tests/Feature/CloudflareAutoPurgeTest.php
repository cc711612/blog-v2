<?php

namespace Tests\Feature;

use App\Jobs\PurgeCloudflareCacheJob;
use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CloudflareAutoPurgeTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'secret-token-123';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://blog.test',
            'cloudflare.purge.enabled' => true,
            'cloudflare.purge.zone_id' => 'zone123',
            'cloudflare.purge.api_token' => self::TOKEN,
            'cloudflare.purge.batch_size' => 100,
            'cloudflare.purge.retries' => 0,
        ]);

        DB::table('users')->insert([
            'id' => 32, 'name' => 'Roy', 'email' => 'roy@example.com',
            'password' => 'x', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function makeArticle(array $attrs = []): Article
    {
        return Article::query()->create($attrs + [
            'id' => 89, 'user_id' => 32, 'title' => 'Hello World', 'slug' => 'hello-world',
            'content' => '<p>x</p>', 'status' => 1,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function expectedShared(): array
    {
        return [
            'https://blog.test/',
            'https://blog.test/article',
            'https://blog.test/sitemap.xml',
            'https://blog.test/feed.xml',
            'https://blog.test/api/articles',
        ];
    }

    public function test_saving_dispatches_after_response_with_full_url_list(): void
    {
        Bus::fake();

        $this->makeArticle();

        Bus::assertDispatchedAfterResponse(PurgeCloudflareCacheJob::class, function (PurgeCloudflareCacheJob $job) {
            $expected = [
                'https://blog.test/article/89/hello-world',
                'https://blog.test/api/articles/89',
                ...$this->expectedShared(),
            ];

            return $job->urls === $expected;
        });
    }

    public function test_slug_change_also_purges_old_url(): void
    {
        $article = $this->makeArticle();
        Bus::fake();

        $article->update(['slug' => 'new-slug']);

        Bus::assertDispatchedAfterResponse(PurgeCloudflareCacheJob::class, function (PurgeCloudflareCacheJob $job) {
            return in_array('https://blog.test/article/89/new-slug', $job->urls, true)
                && in_array('https://blog.test/article/89/hello-world', $job->urls, true);
        });
    }

    public function test_status_change_triggers_purge(): void
    {
        $article = $this->makeArticle();
        Bus::fake();

        $article->update(['status' => 0]);

        Bus::assertDispatchedAfterResponse(PurgeCloudflareCacheJob::class, function (PurgeCloudflareCacheJob $job) {
            return in_array('https://blog.test/article/89/hello-world', $job->urls, true);
        });
    }

    public function test_deleting_triggers_purge(): void
    {
        $article = $this->makeArticle();
        Bus::fake();

        $article->delete();

        Bus::assertDispatchedAfterResponse(PurgeCloudflareCacheJob::class, function (PurgeCloudflareCacheJob $job) {
            return in_array('https://blog.test/article/89/hello-world', $job->urls, true)
                && in_array('https://blog.test/sitemap.xml', $job->urls, true);
        });
    }

    public function test_disabled_or_missing_config_does_not_call_api(): void
    {
        Http::fake();
        Log::spy();

        config(['cloudflare.purge.enabled' => false]);
        $this->makeArticle();
        $this->app->terminate();
        Http::assertNothingSent();
        Log::shouldHaveReceived('debug')->atLeast()->once();
    }

    public function test_missing_token_does_not_call_api(): void
    {
        Http::fake();
        config(['cloudflare.purge.api_token' => null]);

        $this->makeArticle();
        $this->app->terminate();

        Http::assertNothingSent();
    }

    public function test_api_500_does_not_break_save_and_log_has_no_token(): void
    {
        Http::fake(['api.cloudflare.com/*' => Http::response(['success' => false, 'errors' => [['code' => 1000, 'message' => 'boom']]], 500)]);
        $logged = [];
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->atLeast()->once()->andReturnUsing(function ($message, $context = []) use (&$logged) {
            $logged[] = json_encode([$message, $context]);
        });

        $article = $this->makeArticle();
        $this->app->terminate();

        $this->assertNotNull(Article::query()->find(89));
        $this->assertSame('hello-world', $article->fresh()->slug);
        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.cloudflare.com/client/v4/zones/zone123/purge_cache'
            && $r->hasHeader('Authorization', 'Bearer '.self::TOKEN)
            && isset($r->data()['files']));
        $this->assertNotEmpty($logged);
        $this->assertStringNotContainsString(self::TOKEN, implode("\n", $logged));
    }

    public function test_connection_exception_is_swallowed_and_token_not_logged(): void
    {
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('cURL error with '.self::TOKEN));
        $logged = [];
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->atLeast()->once()->andReturnUsing(function ($message, $context = []) use (&$logged) {
            $logged[] = json_encode([$message, $context]);
        });

        $this->makeArticle();
        $this->app->terminate();

        $this->assertNotNull(Article::query()->find(89));
        $this->assertStringNotContainsString(self::TOKEN, implode("\n", $logged));
    }

    public function test_urls_over_batch_limit_are_chunked(): void
    {
        Http::fake(['api.cloudflare.com/*' => Http::response(['success' => true], 200)]);
        config(['cloudflare.purge.batch_size' => 3]);

        $results = app(\App\Modules\Content\Infrastructure\Cloudflare\CloudflareCachePurger::class)
            ->purge(array_map(fn ($i) => "https://blog.test/p/{$i}", range(1, 7)));

        $this->assertCount(3, $results);
        $this->assertSame([3, 3, 1], array_map(fn ($r) => count($r['urls']), $results));
        Http::assertSentCount(3);
    }

    public function test_default_batch_size_is_cloudflare_documented_limit(): void
    {
        $this->assertSame(100, (int) (require base_path('config/cloudflare.php'))['purge']['batch_size']);
    }
}
