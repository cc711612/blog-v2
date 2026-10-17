<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PurgeArticleCacheCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://blog.test',
            'cloudflare.purge.enabled' => true,
            'cloudflare.purge.zone_id' => 'zone123',
            'cloudflare.purge.api_token' => 'cmd-secret-abc',
            'cloudflare.purge.retries' => 0,
        ]);

        DB::table('users')->insert([
            'id' => 32, 'name' => 'Roy', 'email' => 'roy@example.com',
            'password' => 'x', 'created_at' => now(), 'updated_at' => now(),
        ]);

        // 用 DB::table 寫入，模擬維運繞過 model 事件
        foreach ([89 => 'first-post', 90 => 'second-post'] as $id => $slug) {
            DB::table('articles')->insert([
                'id' => $id, 'user_id' => 32, 'title' => "Post {$id}", 'slug' => $slug,
                'content' => 'x', 'status' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function test_purges_given_ids_and_prints_urls_and_response(): void
    {
        Http::fake(['api.cloudflare.com/*' => Http::response(['success' => true, 'result' => ['id' => 'x']], 200)]);

        $this->artisan('cloudflare:purge-article', ['id' => [89]])
            ->expectsOutputToContain('https://blog.test/article/89/first-post')
            ->expectsOutputToContain('https://blog.test/sitemap.xml')
            ->expectsOutputToContain('HTTP 200 ok')
            ->assertExitCode(0);

        Http::assertSent(function (Request $r) {
            $files = $r->data()['files'];

            return in_array('https://blog.test/article/89/first-post', $files, true)
                && ! in_array('https://blog.test/article/90/second-post', $files, true);
        });
    }

    public function test_all_articles_option_includes_every_article(): void
    {
        Http::fake(['api.cloudflare.com/*' => Http::response(['success' => true], 200)]);

        $this->artisan('cloudflare:purge-article', ['--all-articles' => true])->assertExitCode(0);

        Http::assertSent(function (Request $r) {
            $files = $r->data()['files'];

            return in_array('https://blog.test/article/89/first-post', $files, true)
                && in_array('https://blog.test/article/90/second-post', $files, true);
        });
    }

    public function test_reports_api_failure_with_nonzero_exit(): void
    {
        Http::fake(['api.cloudflare.com/*' => Http::response(['success' => false, 'errors' => [['code' => 9109, 'message' => 'Invalid token']]], 403)]);

        $this->artisan('cloudflare:purge-article', ['id' => [89]])
            ->expectsOutputToContain('HTTP 403 9109 Invalid token')
            ->assertExitCode(1);
    }

    public function test_disabled_config_does_not_call_api(): void
    {
        Http::fake();
        config(['cloudflare.purge.enabled' => false]);

        $this->artisan('cloudflare:purge-article', ['id' => [89]])
            ->expectsOutputToContain('未啟用')
            ->assertExitCode(1);

        Http::assertNothingSent();
    }

    public function test_requires_id_or_all_option(): void
    {
        Http::fake();

        $this->artisan('cloudflare:purge-article')->assertExitCode(1);
        Http::assertNothingSent();
    }

    public function test_db_table_writes_do_not_trigger_observer(): void
    {
        Bus::fake();
        DB::table('articles')->where('id', 89)->update(['title' => 'changed']);

        Bus::assertNothingDispatched();
    }
}
