<?php

namespace App\Providers;

use App\Modules\Content\Domain\Repositories\ArticleRepositoryInterface;
use App\Modules\Content\Domain\Repositories\CommentRepositoryInterface;
use App\Modules\Content\Infrastructure\Persistence\Eloquent\EloquentArticleRepository;
use App\Modules\Content\Infrastructure\Persistence\Eloquent\EloquentCommentRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ArticleRepositoryInterface::class, EloquentArticleRepository::class);
        $this->app->singleton(CommentRepositoryInterface::class, EloquentCommentRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Livewire 更新端點（/livewire/update）已排除 CSRF，補上每 IP 限流。
        // 留言搜尋是 debounce 300ms、輸入留言用 wire:model.live，一般使用者遠低於此上限。
        RateLimiter::for('livewire', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));

        // 路由名稱必須以 livewire.update 結尾，Livewire 才找得到更新端點
        Livewire::setUpdateRoute(fn ($handle) => Route::post('/livewire/update', $handle)
            ->middleware(['web', 'throttle:livewire'])
            ->name('livewire.update'));
    }
}
