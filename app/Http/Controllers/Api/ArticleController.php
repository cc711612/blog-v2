<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Modules\Content\Application\Services\ListArticlesService;
use App\Modules\Content\Application\Services\ShowArticleService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    public function __construct(
        private readonly ListArticlesService $listArticles,
        private readonly ShowArticleService $showArticle,
    ) {
    }

    public function index(Request $request)
    {
        $page = max(1, (int) $request->integer('page', 1));
        $search = Str::limit(trim((string) $request->query('search', '')), 100, '');
        $articles = $this->listArticles->handle(32, $page, 10, $search);

        return ArticleResource::collection(collect($articles->items()));
    }

    public function show(int $id)
    {
        $article = $this->showArticle->handle($id);
        abort_if($article === null, 404);

        return new ArticleResource([
            'id' => $article->id,
            'title' => $article->title,
            'content_preview' => \Illuminate\Support\Str::limit(strip_tags($article->contentHtml), 120),
            'author_name' => $article->authorName,
            'created_at' => $article->createdAt ?? $article->updatedAt,
            'content_html' => $article->contentHtml,
            'seo' => $article->seo,
            'updated_at' => $article->updatedAt,
            'comments_count' => 0,
        ]);
    }
}
