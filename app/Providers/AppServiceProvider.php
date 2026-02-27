<?php

namespace App\Providers;

use App\Modules\Content\Domain\Repositories\ArticleRepositoryInterface;
use App\Modules\Content\Domain\Repositories\CommentRepositoryInterface;
use App\Modules\Content\Infrastructure\Persistence\Eloquent\EloquentArticleRepository;
use App\Modules\Content\Infrastructure\Persistence\Eloquent\EloquentCommentRepository;
use Illuminate\Support\ServiceProvider;

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
        //
    }
}
