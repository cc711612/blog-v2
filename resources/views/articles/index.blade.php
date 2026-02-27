@extends('layouts.app')

@section('title', $seoMeta->title)
@section('meta')
    <meta name="description" content="{{ $seoMeta->description }}">
    <meta name="twitter:title" content="{{ $seoMeta->title }}">
    <meta name="twitter:description" content="{{ $seoMeta->description }}">
    <meta name="keyword" content="{{ $seoMeta->keywords }}">
    <meta property="og:title" content="{{ $seoMeta->title }}">
    <meta property="og:description" content="{{ $seoMeta->description }}">
    <meta property="og:url" content="{{ $seoMeta->canonical }}">
    <meta property="og:site_name" content="{{ $seoMeta->siteName }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="zh_TW">
    <meta property="og:image" content="{{ $seoMeta->ogImage }}">
    <link rel="canonical" href="{{ $seoMeta->canonical }}">
    @if($seoMeta->robots)
        <meta name="robots" content="{{ $seoMeta->robots }}">
    @endif
@endsection

@section('content')
    <section class="hero">
        <h1>The Roy Deploy</h1>
        <p class="meta">從 <code>Request</code> 到 <code>Response</code> 的技術實踐</p>
        <hr style="width: 50px; border: 1px solid #ddd; margin: 20px 0;">
        <p class="sub-meta">專注於後端維運架構</p>
    </section>

    <div class="content-grid">
        <section>
            <form class="search" action="{{ route('articles.index') }}" method="get">
                <input type="text" name="search" value="{{ $search }}" placeholder="搜尋文章主題，例如 Laravel / Docker" />
                <button class="theme-btn" type="submit">搜尋</button>
            </form>

            @forelse($articles as $article)
                <section class="article-item">
                    <h2>
                        <a href="{{ route('articles.show', ['id' => $article['id']]) }}">{{ $article['title'] }}</a>
                    </h2>
                    <p>{{ \Illuminate\Support\Str::limit(strip_tags($article['content_html']), 150) }}</p>
                    <div class="meta">
                        {{ $article['author_name'] }} • {{ $article['created_at']->format('Y-m-d') }} • {{ $article['comments_count'] }} comments
                    </div>
                </section>
            @empty
                <p>沒有符合條件的文章。</p>
            @endforelse

            @if($articles->lastPage() > 1)
                <nav class="pager" aria-label="Article pages">
                    @for($page = 1; $page <= $articles->lastPage(); $page++)
                        <a
                            class="{{ $page === $articles->currentPage() ? 'active' : '' }}"
                            href="{{ route('articles.index', array_filter(['page' => $page, 'search' => $search !== '' ? $search : null])) }}"
                        >
                            {{ $page }}
                        </a>
                    @endfor
                </nav>
            @endif
        </section>

        <aside class="sidebar">
            <section class="side-card">
                <h3>最新文章</h3>
                <ul class="side-list">
                    @forelse($recentArticles as $recent)
                        <li>
                            <a href="{{ route('articles.show', ['id' => $recent['id']]) }}">{{ \Illuminate\Support\Str::limit($recent['title'], 44) }}</a>
                            <small>{{ $recent['created_at']->format('Y-m-d') }}</small>
                        </li>
                    @empty
                        <li><span>暫無資料</span></li>
                    @endforelse
                </ul>
            </section>

            <section class="side-card">
                <h3>熱門關鍵字</h3>
                <div class="tags">
                    @foreach($popularKeywords as $keyword)
                        <a href="{{ route('articles.index', ['search' => $keyword]) }}">{{ $keyword }}</a>
                    @endforeach
                </div>
            </section>
        </aside>
    </div>
@endsection
