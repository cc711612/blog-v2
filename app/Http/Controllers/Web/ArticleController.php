<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Content\Application\Services\ListArticlesService;
use App\Modules\Content\Application\Services\ShowArticleService;
use App\Modules\Content\Domain\Entities\ArticleEntity;
use App\Support\ArticleSlug;
use App\ViewModels\SeoMetaData;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    public function __construct(
        private readonly ListArticlesService $listArticles,
        private readonly ShowArticleService $showArticle,
    ) {
    }

    /**
     * Render article listing page.
     */
    public function index(Request $request)
    {
        $page = max(1, (int) $request->integer('page', 1));
        $search = Str::limit(trim((string) $request->query('search', '')), 100, '');
        $articles = $this->listArticles->handle(32, $page, 10, $search);
        $recentArticles = collect($this->listArticles->handle(32, 1, 5)->items())
            ->map(fn (array $article) => [
                'id' => (int) $article['id'],
                'slug' => $article['slug'],
                'title' => (string) $article['title'],
                'created_at' => $article['created_at'],
            ]);

        $popularKeywords = $this->buildPopularKeywords();
        $seoMeta = $this->buildIndexSeoMeta($search, $page);

        return view('articles.index', [
            'articles' => $articles,
            'search' => $search,
            'recentArticles' => $recentArticles,
            'popularKeywords' => $popularKeywords,
            'seoMeta' => $seoMeta,
        ]);
    }

    /**
     * Render article detail page.
     */
    public function show(int $id, ?string $slug = null)
    {
        $article = $this->showArticle->handle($id);
        abort_if($article === null, 404);

        $canonicalSlug = ArticleSlug::from($article->slug, $article->title, $article->id);
        if ($slug !== $canonicalSlug) {
            return redirect()->route('articles.show', ['id' => $article->id, 'slug' => $canonicalSlug], 301);
        }

        $seoMeta = $this->buildShowSeoMeta($article);

        return view('articles.show', [
            'article' => $article,
            'seoMeta' => $seoMeta,
        ]);
    }

    /**
     * Build SEO metadata for listing page.
     *
     * @return SeoMetaData
     */
    private function buildIndexSeoMeta(string $search, int $page): SeoMetaData
    {
        $siteName = config('app.name', 'royBlog');
        $keywords = 'blog,部落格,個人文章,個人部落格,工程師,Laravel,laravel,php,後端工程師,backend';
        $isPaged = $page > 1;

        $title = $siteName;
        $description = '文章列表';
        if ($search !== '') {
            $title = $siteName.' - 搜尋: '.$search;
            $description = '搜尋結果: '.$search;
        } elseif ($isPaged) {
            $title = $siteName.' - 第 '.$page.' 頁';
        }

        return new SeoMetaData(
            title: $title,
            description: $description,
            keywords: $keywords,
            canonical: route('articles.index', array_filter([
                'page' => $isPaged ? $page : null,
                'search' => $search !== '' ? $search : null,
            ])),
            siteName: $siteName,
            ogImage: url('/favicon.ico'),
            robots: $search !== '' ? 'noindex,follow' : null,
        );
    }

    /**
     * Build SEO metadata for article detail page.
     *
     * @return SeoMetaData
     */
    private function buildShowSeoMeta(ArticleEntity $article): SeoMetaData
    {
        $defaultKeywords = 'blog,部落格,個人文章,個人部落格,工程師,Laravel,laravel,php,後端工程師,backend';
        $fallbackDescription = Str::limit(strip_tags((string) $article->contentHtml), 150);
        $description = (string) (data_get($article->seo, 'description') ?? $fallbackDescription);
        $keywords = (string) (data_get($article->seo, 'keyword')
            ?? data_get($article->seo, 'keywords')
            ?? $defaultKeywords);
        $ogImage = (string) (data_get($article->seo, 'image') ?? url('/favicon.ico'));
        $title = (string) $article->title;

        return new SeoMetaData(
            title: $title,
            description: $description,
            keywords: $keywords,
            canonical: route('articles.show', [
                'id' => $article->id,
                'slug' => ArticleSlug::from($article->slug, $article->title, $article->id),
            ]),
            siteName: config('app.name', 'royBlog'),
            ogImage: $ogImage,
            articleAuthor: (string) $article->authorName,
            articleModifiedTime: $article->updatedAt->toIso8601String(),
        );
    }

    /**
     * Return fixed popular keyword list.
     *
     * @return array<int, string>
     */
    private function buildPopularKeywords(): array
    {
        return [
            'Docker',
            'Linux',
            'Node.js',
            'Nginx',
            'Ubuntu',
            'Laravel',
            'PHP',
            'MySQL',
            'Redis',
            'Git',
            'JavaScript',
            'Vue.js',
            'AWS',
            'Python',
            'React',
        ];
    }
}
