<?php

namespace App\Modules\Content\Infrastructure\Persistence\InMemory;

use App\Modules\Content\Domain\Entities\ArticleEntity;
use App\Modules\Content\Domain\Repositories\ArticleRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * In-memory fallback repository for articles.
 */
class ArticleInMemoryRepository implements ArticleRepositoryInterface
{
    /** @return Collection<int, ArticleEntity> */
    private function dataset(): Collection
    {
        return collect([
            new ArticleEntity(80, 32, 'Laravel Telescope Complete Guide', '<h2>Overview</h2><p>Telescope helps inspect requests, SQL, queue, and exceptions.</p><pre><code class="language-bash">composer require laravel/telescope --dev\nphp artisan telescope:install\nphp artisan migrate</code></pre>', 1, CarbonImmutable::parse('2026-02-27 03:22:55')),
            new ArticleEntity(79, 32, 'Redis Cache Strategy in Laravel', '<h2>Cache Aside</h2><p>Use cache first, DB fallback and TTL policy.</p><pre><code class="language-php">$user = Cache::remember("user:$id", 600, fn() => User::find($id));</code></pre>', 1, CarbonImmutable::parse('2026-02-27 03:22:55')),
            new ArticleEntity(78, 32, 'Repository Pattern + Service Layer', '<h2>Architecture</h2><p>Separate domain logic from framework details.</p>', 1, CarbonImmutable::parse('2026-02-27 03:22:55')),
            new ArticleEntity(77, 32, 'Laravel N+1 Problem Guide', '<h2>N+1</h2><p>Use eager loading and query profiling.</p>', 1, CarbonImmutable::parse('2026-02-27 03:22:55')),
            new ArticleEntity(76, 32, 'MySQL Index and Explain', '<h2>Index</h2><p>Measure query plans with EXPLAIN.</p>', 1, CarbonImmutable::parse('2026-02-27 03:22:55')),
            new ArticleEntity(75, 32, 'Laravel Scheduler Guide', '<h2>Scheduler</h2><p>Define schedule in console kernel and monitor jobs.</p>', 1, CarbonImmutable::parse('2026-02-27 03:22:55')),
            new ArticleEntity(74, 32, 'Docker Compose Multi PHP Versions', '<h2>Docker</h2><p>Run 5.6/7.2/7.4/8.1/8.2 for migration support.</p>', 1, CarbonImmutable::parse('2026-02-27 03:22:55')),
            new ArticleEntity(73, 32, 'Laravel Queue + Horizon', '<h2>Queue</h2><p>Asynchronous jobs with monitoring dashboard.</p>', 1, CarbonImmutable::parse('2026-02-27 03:22:54')),
            new ArticleEntity(72, 32, 'Laravel Reverb WebSocket', '<h2>Realtime</h2><p>Use official server for websocket channels.</p>', 1, CarbonImmutable::parse('2026-02-27 03:22:54')),
            new ArticleEntity(71, 32, 'SSE with PHP Example', '<h2>SSE</h2><p>Server-sent events in plain PHP.</p>', 1, CarbonImmutable::parse('2026-02-27 03:22:54')),
            new ArticleEntity(70, 32, 'WebSocket vs SSE', '<h2>Protocol</h2><p>Pick protocol by duplex and infra constraints.</p>', 1, CarbonImmutable::parse('2024-08-29 05:32:54')),
            new ArticleEntity(69, 32, 'OpenAI Chat API in Laravel', '<h2>AI</h2><p>Integrate chat completion endpoint and cache response.</p>', 1, CarbonImmutable::parse('2026-02-27 03:22:54')),
            new ArticleEntity(68, 32, 'GitLab CI/CD Deployment', '<h2>CI/CD</h2><p>Build-test-deploy pipeline for Laravel app.</p>', 1, CarbonImmutable::parse('2026-02-27 03:22:54')),
            new ArticleEntity(67, 32, 'Laravel Octane with Swoole', '<h2>Octane</h2><p>Keep workers warm and reduce boot overhead.</p>', 1, CarbonImmutable::parse('2026-02-27 03:22:54')),
            new ArticleEntity(66, 32, 'whereIn slow or empty data', '<h2>Database</h2><p>Validate bindings and input size to avoid empty set.</p>', 1, CarbonImmutable::parse('2026-02-27 03:22:54')),
            new ArticleEntity(65, 32, 'Docker port occupied issue', '<h2>Docker</h2><p>Detect conflict and map available host port.</p>', 1, CarbonImmutable::parse('2026-02-27 03:22:54')),
            new ArticleEntity(64, 32, 'Install latest Node.js on Ubuntu', '<h2>Node.js</h2><p>Use NodeSource and verify npm versions.</p>', 1, CarbonImmutable::parse('2026-02-27 03:22:54')),
            new ArticleEntity(63, 32, 'Nginx Load Balance', '<h2>Nginx</h2><p>Use upstream with health checks and failover.</p>', 1, CarbonImmutable::parse('2026-02-27 03:22:54')),
            new ArticleEntity(62, 32, 'Linux disk usage check', '<h2>Linux</h2><p>Use df and du to inspect disk usage safely.</p>', 1, CarbonImmutable::parse('2026-02-27 03:22:54')),
            new ArticleEntity(60, 32, 'Taiwan stock free API', '<h2>Stock API</h2><p>Simple API sample for stock quote retrieval.</p>', 1, CarbonImmutable::parse('2026-02-27 03:21:03')),
            new ArticleEntity(59, 32, 'GCP VM cannot SSH', '<h2>GCP</h2><p>Recover no-space issue and restore ssh access.</p>', 1, CarbonImmutable::parse('2026-02-27 03:22:54')),
            new ArticleEntity(58, 32, 'SSL certificate issuer problem', '<h2>SSL</h2><p>Fix CA bundle path for curl and openssl.</p>', 1, CarbonImmutable::parse('2026-02-27 03:22:54')),
            new ArticleEntity(57, 32, 'Supervisor for cron queue socket', '<h2>Supervisor</h2><p>Manage worker process lifecycle on Linux.</p>', 1, CarbonImmutable::parse('2026-02-27 03:22:54')),
            new ArticleEntity(56, 32, 'Websocket chatroom in Laravel', '<h2>Chat</h2><p>Build room events and message broadcasting.</p>', 1, CarbonImmutable::parse('2026-02-27 03:22:54')),
            new ArticleEntity(53, 32, 'Websocket online users', '<h2>Realtime</h2><p>Track online users with cache heartbeat.</p>', 1, CarbonImmutable::parse('2026-02-27 03:22:54')),
            new ArticleEntity(51, 32, 'PhpMyAdmin on Ubuntu', '<h2>Database</h2><p>Secure phpMyAdmin in production environment.</p>', 1, CarbonImmutable::parse('2026-02-27 03:22:54')),
            new ArticleEntity(44, 32, 'Laravel deploy on Ubuntu', '<h2>Deploy</h2><p>Nginx + MariaDB + PHP setup checklist.</p>', 1, CarbonImmutable::parse('2026-02-27 03:22:54')),
        ]);
    }

    /**
     * @param int $userId
     * @param int $page
     * @param int $perPage
     * @param string $search
     * @return LengthAwarePaginator
     */
    public function paginatePublishedByUser(int $userId, int $page, int $perPage, string $search = ''): LengthAwarePaginator
    {
        $filtered = $this->dataset()
            ->filter(fn (ArticleEntity $article) => $article->status === 1 && $article->userId === $userId)
            ->when($search !== '', function (Collection $items) use ($search) {
                return $items->filter(function (ArticleEntity $article) use ($search) {
                    return Str::contains(Str::lower($article->title), Str::lower($search))
                        || Str::contains(Str::lower(strip_tags($article->contentHtml)), Str::lower($search));
                });
            })
            ->sortByDesc(fn (ArticleEntity $article) => $article->updatedAt->timestamp)
            ->values();

        $offset = max(0, ($page - 1) * $perPage);

        return new LengthAwarePaginator(
            $filtered->slice($offset, $perPage)->values()->all(),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * @param int $id
     * @return ArticleEntity|null
     */
    public function findPublishedById(int $id): ?ArticleEntity
    {
        return $this->dataset()->first(
            fn (ArticleEntity $article) => $article->id === $id && $article->status === 1
        );
    }

    /**
     * @param int $userId
     * @return Collection<int, ArticleEntity>
     */
    public function allPublishedByUser(int $userId): Collection
    {
        return $this->dataset()
            ->filter(fn (ArticleEntity $article) => $article->status === 1 && $article->userId === $userId)
            ->sortByDesc(fn (ArticleEntity $article) => $article->updatedAt->timestamp)
            ->values();
    }
}
