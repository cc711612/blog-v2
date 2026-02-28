<!DOCTYPE html>
<html lang="zh-Hant" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f766e">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="RoyBlog">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="RoyBlog">
    <link rel="manifest" href="{{ route('manifest') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ url('/images/icons/blog-icon-192x192.png') }}">
    <link rel="icon" type="image/png" sizes="512x512" href="{{ url('/images/icons/blog-icon-512x512.png') }}">
    <link rel="apple-touch-icon" href="{{ url('/images/icons/blog-icon-180x180.png') }}">
    <meta name="msapplication-TileColor" content="#f6f7f9">
    <meta name="msapplication-TileImage" content="{{ url('/images/icons/blog-icon-144x144.png') }}">
    <link href="{{ url('/images/icons/blog-splash-640x1136.png') }}" media="(device-width: 320px) and (device-height: 568px) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image" />
    <link href="{{ url('/images/icons/blog-splash-750x1334.png') }}" media="(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image" />
    <link href="{{ url('/images/icons/blog-splash-1125x2436.png') }}" media="(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3)" rel="apple-touch-startup-image" />
    <link href="{{ url('/images/icons/blog-splash-828x1792.png') }}" media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image" />
    <link href="{{ url('/images/icons/blog-splash-1242x2688.png') }}" media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 3)" rel="apple-touch-startup-image" />
    <link href="{{ url('/images/icons/blog-splash-1536x2048.png') }}" media="(device-width: 768px) and (device-height: 1024px) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image" />
    <link href="{{ url('/images/icons/blog-splash-1668x2224.png') }}" media="(device-width: 834px) and (device-height: 1112px) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image" />
    <link href="{{ url('/images/icons/blog-splash-1668x2388.png') }}" media="(device-width: 834px) and (device-height: 1194px) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image" />
    <link href="{{ url('/images/icons/blog-splash-2048x2732.png') }}" media="(device-width: 1024px) and (device-height: 1366px) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image" />
    <title>@yield('title', 'Roy-Try-Catch')</title>
    @yield('meta')
    @if(config('services.google.tag') || config('services.google.analytics_id'))
        <link rel="preconnect" href="https://www.google-analytics.com">
        <link rel="preconnect" href="https://www.googletagmanager.com">
        <link rel="dns-prefetch" href="https://www.google-analytics.com">
        <link rel="dns-prefetch" href="https://www.googletagmanager.com">
    @endif
    @include('layouts.tracking_header')
    @stack('head')
    <style>
        :root {
            --bg: #f6f7f9;
            --surface: #ffffff;
            --text: #1f2937;
            --muted: #6b7280;
            --primary: #0f766e;
            --accent: #c2410c;
            --border: #d1d5db;
            --code-bg: #2f333d;
            --code-text: #cdd3de;
            --code-border: #292c33;
        }
        [data-theme="dark"] {
            --bg: #0f172a;
            --surface: #111827;
            --text: #e5e7eb;
            --muted: #9ca3af;
            --primary: #14b8a6;
            --accent: #fb923c;
            --border: #374151;
            --code-bg: #111827;
            --code-text: #e5e7eb;
            --code-border: #374151;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Noto Sans TC", "Segoe UI", sans-serif;
            background: radial-gradient(circle at top right, rgba(20, 184, 166, 0.12), transparent 40%), var(--bg);
            color: var(--text);
        }
        .container { max-width: 960px; margin: 0 auto; padding: 24px 16px 80px; }
        .topbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .brand { color: var(--primary); text-decoration: none; font-weight: 700; letter-spacing: 0.04em; }
        .theme-btn { border: 1px solid var(--border); background: var(--surface); color: var(--text); padding: 8px 12px; border-radius: 999px; cursor: pointer; }
        .panel { background: var(--surface); border: 1px solid var(--border); border-radius: 18px; padding: 24px; box-shadow: 0 18px 40px rgba(15, 23, 42, 0.09); }
        .hero { margin-bottom: 18px; }
        .hero h1 { margin: 0; font-size: 2rem; letter-spacing: 0.01em; }
        .content-grid { display: grid; grid-template-columns: minmax(0, 1fr) 280px; gap: 18px; align-items: start; }
        .article-item { background: color-mix(in srgb, var(--surface) 90%, transparent); border: 1px solid var(--border); border-radius: 14px; padding: 16px; }
        .article-item + .article-item { margin-top: 12px; }
        .article-item h2 { margin: 0 0 8px; font-size: 1.2rem; line-height: 1.4; }
        .article-item p { margin: 0 0 8px; color: var(--muted); }
        .sidebar { display: grid; gap: 12px; }
        .side-card { border: 1px solid var(--border); border-radius: 14px; padding: 14px; background: linear-gradient(180deg, color-mix(in srgb, var(--surface) 96%, transparent), var(--surface)); }
        .side-card h3 { margin: 0 0 10px; font-size: 0.98rem; color: var(--text); }
        .side-list { list-style: none; margin: 0; padding: 0; display: grid; gap: 10px; }
        .side-list li { display: grid; gap: 3px; }
        .side-list a { text-decoration: none; color: var(--text); font-size: 0.94rem; }
        .side-list small { color: var(--muted); font-size: 0.78rem; }
        .tags { display: flex; flex-wrap: wrap; gap: 7px; }
        .tags a { text-decoration: none; border: 1px solid var(--border); color: var(--text); background: var(--surface); border-radius: 999px; font-size: 0.83rem; padding: 5px 10px; }
        .meta { color: var(--muted); font-size: 0.9rem; }
        a { color: var(--primary); }
        .pager { margin-top: 20px; display: flex; flex-wrap: wrap; gap: 8px; }
        .pager a { border: 1px solid var(--border); border-radius: 8px; padding: 6px 10px; text-decoration: none; color: var(--text); background: var(--surface); }
        .pager a.active { border-color: var(--primary); color: var(--primary); font-weight: 700; }
        article h2, article h3 { color: var(--text); }
        article p { line-height: 1.8; }
        .error { color: #dc2626; margin: 0; }
        .search { display: flex; gap: 8px; margin: 14px 0 16px; }
        .search input { flex: 1; border: 1px solid var(--border); border-radius: 8px; background: var(--surface); color: var(--text); padding: 10px; }
        .site-footer { margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--border); text-align: center; }
        .social-links { display: inline-flex; gap: 10px; flex-wrap: wrap; justify-content: center; }
        .social-links a {
            min-height: 38px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 8px 12px;
            text-decoration: none;
            border: 1px solid var(--border);
            color: var(--text);
            background: color-mix(in srgb, var(--surface) 90%, transparent);
            font-weight: 600;
            font-size: 0.82rem;
        }
        .social-links svg { width: 15px; height: 15px; }
        .site-footer small { display: block; margin-top: 10px; color: var(--muted); }
        @media (max-width: 768px) {
            .container { padding: 18px 12px 56px; }
            .article-item h2 { font-size: 1.2rem; }
            .hero h1 { font-size: 1.7rem; }
            .content-grid { grid-template-columns: 1fr; }
            .sidebar { order: -1; }
        }
    </style>
    @stack('page-styles')
</head>
<body>
@include('layouts.tracking_noscript')
<div class="container">
    <div class="topbar">
        <a class="brand" href="{{ route('articles.index') }}">Roy-Try-Catch</a>
        <button id="themeToggle" class="theme-btn" type="button">Theme</button>
    </div>

    <main class="panel">
        @yield('content')

        <footer class="site-footer">
            <div class="social-links">
                <a href="https://github.com/cc711612" target="_blank" rel="noopener noreferrer" aria-label="GitHub">
                    <svg viewBox="0 0 24 24" aria-hidden="true" fill="currentColor"><path d="M12 .5a12 12 0 0 0-3.79 23.39c.6.1.82-.26.82-.58v-2.23c-3.34.73-4.04-1.61-4.04-1.61-.55-1.38-1.33-1.75-1.33-1.75-1.09-.74.08-.73.08-.73 1.2.09 1.84 1.23 1.84 1.23 1.07 1.83 2.8 1.3 3.48 1 .11-.77.42-1.3.76-1.6-2.66-.3-5.47-1.33-5.47-5.93 0-1.31.46-2.38 1.23-3.22-.13-.3-.53-1.52.12-3.17 0 0 1-.32 3.3 1.23a11.43 11.43 0 0 1 6 0c2.3-1.55 3.3-1.23 3.3-1.23.65 1.65.25 2.87.12 3.17.77.84 1.23 1.91 1.23 3.22 0 4.61-2.82 5.63-5.5 5.92.43.37.82 1.1.82 2.23v3.3c0 .32.22.69.83.58A12 12 0 0 0 12 .5Z"/></svg>
                    <span>GitHub</span>
                </a>
                <a href="https://www.cakeresume.com/cc711612" target="_blank" rel="noopener noreferrer" aria-label="CakeResume">
                    <svg viewBox="0 0 24 24" aria-hidden="true" fill="currentColor"><path d="M4 11h16v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-7Zm16-2h-2V7a2 2 0 0 0-2-2h-1V3h-2v2h-2V3H9v2H8a2 2 0 0 0-2 2v2H4V7a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v2Z"/></svg>
                    <span>CakeResume</span>
                </a>
                <a href="https://www.linkedin.com/in/%E5%86%A0%E8%9E%8D-%E6%9D%8E-baa87b224/" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn">
                    <svg viewBox="0 0 24 24" aria-hidden="true" fill="currentColor"><path d="M5.5 3a2.5 2.5 0 1 1 0 5.001A2.5 2.5 0 0 1 5.5 3ZM4 9h3v12H4V9Zm5 0h2.88v1.64h.04c.4-.76 1.38-1.56 2.84-1.56 3.04 0 3.6 2 3.6 4.6V21h-3v-6.44c0-1.53-.03-3.5-2.13-3.5-2.14 0-2.47 1.67-2.47 3.39V21H9V9Z"/></svg>
                    <span>LinkedIn</span>
                </a>
            </div>
            <small>Copyright &copy; {{ now()->year }} Roy</small>
        </footer>
    </main>
</div>
<script>
    (() => {
        const root = document.documentElement;
        const saved = localStorage.getItem('theme') || 'system';
        const apply = (mode) => {
            if (mode === 'system') {
                root.dataset.theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                return;
            }
            root.dataset.theme = mode;
        };
        apply(saved);
        const btn = document.getElementById('themeToggle');
        btn?.addEventListener('click', () => {
            const current = localStorage.getItem('theme') || 'system';
            const next = current === 'light' ? 'dark' : current === 'dark' ? 'system' : 'light';
            localStorage.setItem('theme', next);
            apply(next);
            btn.textContent = `Theme: ${next}`;
        });
        btn.textContent = `Theme: ${saved}`;
    })();
</script>
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js').catch(() => {});
        });
    }
</script>
@stack('scripts')
</body>
</html>
