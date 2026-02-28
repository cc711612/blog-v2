@extends('layouts.app')

@push('head')
    @livewireStyles
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/styles/github-dark.min.css">
@endpush

@push('page-styles')
    <style>
        .article-content pre {
            box-sizing: border-box;
            background: var(--code-bg);
            color: var(--code-text);
            font-family: "JetBrains Mono", "Fira Code", monospace;
            line-height: 1.6;
            margin: 1.2em 0;
            overflow: auto;
            padding: 12px 15px;
            border: 1px solid var(--code-border);
            border-radius: 6px;
            width: 100%;
        }
        .article-content pre code { color: inherit; white-space: pre; display: block; }
        .article-content .hljs {
            background: var(--code-bg);
            color: var(--code-text);
            border-radius: 6px;
            padding: 0;
        }
        .article-content :not(pre) > code {
            background: color-mix(in srgb, var(--primary) 14%, transparent);
            border: 1px solid color-mix(in srgb, var(--primary) 20%, var(--border));
            border-radius: 4px;
            padding: 0.1em 0.3em;
        }
        .article-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.2em 0;
            font-size: 0.95em;
        }
        .article-content table th,
        .article-content table td {
            border: 1px solid var(--border);
            padding: 10px 14px;
            text-align: left;
            vertical-align: top;
        }
        .article-content table thead th {
            background: color-mix(in srgb, var(--primary) 12%, var(--surface));
            color: var(--text);
            font-weight: 600;
            white-space: nowrap;
        }
        .article-content table tbody tr:nth-child(even) {
            background: color-mix(in srgb, var(--surface) 60%, transparent);
        }
        .article-content table tbody tr:hover {
            background: color-mix(in srgb, var(--primary) 6%, transparent);
        }
        .comment-panel { margin-top: 30px; }
        .comment-item { border-top: 1px solid var(--border); padding: 12px 0; }
        .comment-item p { margin: 0 0 6px; }
        .comment-item small { color: var(--muted); }
        .comment-form { margin-top: 12px; display: grid; gap: 8px; }
        .comment-form input, .comment-form textarea {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--surface);
            color: var(--text);
            padding: 10px;
        }
        .comment-form button {
            justify-self: start;
            border: 0;
            border-radius: 8px;
            background: var(--primary);
            color: #fff;
            padding: 10px 16px;
            cursor: pointer;
        }
        .comment-tools {
            display: grid;
            grid-template-columns: 1fr 110px;
            gap: 8px;
            margin-bottom: 10px;
        }
        .comment-tools input, .comment-tools select {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--surface);
            color: var(--text);
            padding: 9px 10px;
        }
        @media (max-width: 768px) {
            .comment-tools { grid-template-columns: 1fr; }
        }
    </style>
@endpush

@push('scripts')
    @livewireScripts
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/highlight.min.js" defer></script>
    <script>
        (() => {
            const LANG_RULES = [
                { lang: 'php', test: /<\?php|\$\w+|->|::|\b(public|private|protected|function|namespace|use)\b/ },
                { lang: 'javascript', test: /\b(const|let|var|=>|import\s|export\s|function\s*\()/ },
                { lang: 'typescript', test: /\b(interface|type\s+\w+\s*=|implements|readonly)\b/ },
                { lang: 'python', test: /\bdef\s+\w+\(|\bimport\s+\w+|\bprint\(/ },
                { lang: 'bash', test: /^\s*\$\s+|\b(curl|grep|sed|awk|chmod|chown|systemctl|apt|yum)\b/m },
                { lang: 'sql', test: /\b(SELECT|INSERT|UPDATE|DELETE|FROM|WHERE|JOIN|GROUP BY|ORDER BY)\b/i },
                { lang: 'json', test: /^\s*[\[{](?:.|\n)*[\]}]\s*$/ },
                { lang: 'html', test: /<\/?[a-z][\s\S]*>/i },
                { lang: 'css', test: /[.#]?[a-zA-Z0-9_-]+\s*\{[^}]*:[^}]*\}/ },
                { lang: 'yaml', test: /^\s*[\w-]+:\s.+/m },
            ];

            const hasExplicitLanguage = (codeEl) =>
                [...codeEl.classList].some((name) => name.startsWith('language-') || name.startsWith('lang-'));

            const normalizeLanguage = (raw) => {
                if (!raw) {
                    return null;
                }

                const key = raw.toLowerCase();
                const aliases = {
                    js: 'javascript',
                    node: 'javascript',
                    ts: 'typescript',
                    py: 'python',
                    sh: 'bash',
                    shell: 'bash',
                    zsh: 'bash',
                    yml: 'yaml',
                    mysql: 'sql',
                };

                return aliases[key] || key;
            };

            const pickLegacyLanguage = (codeEl) => {
                const preEl = codeEl.closest('pre');
                const rawClasses = [
                    ...codeEl.classList,
                    ...(preEl ? [...preEl.classList] : []),
                ];

                const direct = rawClasses.find((name) => /^(php|js|javascript|ts|typescript|python|bash|shell|sql|json|html|css|yaml|yml)$/i.test(name));
                if (direct) {
                    return normalizeLanguage(direct);
                }

                const patterns = [
                    /language-([a-z0-9+#-]+)/i,
                    /lang(?:uage)?[:_-]([a-z0-9+#-]+)/i,
                    /brush[:_-]\s*([a-z0-9+#-]+)/i,
                    /highlight[:_-]([a-z0-9+#-]+)/i,
                ];

                for (const name of rawClasses) {
                    for (const pattern of patterns) {
                        const match = name.match(pattern);
                        if (match && match[1]) {
                            return normalizeLanguage(match[1]);
                        }
                    }
                }

                const attrCandidates = [
                    codeEl.getAttribute('data-language'),
                    codeEl.getAttribute('data-lang'),
                    preEl ? preEl.getAttribute('data-language') : null,
                    preEl ? preEl.getAttribute('data-lang') : null,
                ];

                for (const raw of attrCandidates) {
                    const normalized = normalizeLanguage(raw);
                    if (normalized) {
                        return normalized;
                    }
                }

                return null;
            };

            const detectLanguage = (content) => {
                for (const rule of LANG_RULES) {
                    if (rule.test.test(content)) {
                        return rule.lang;
                    }
                }

                return null;
            };

            const highlightCodeBlocks = () => {
                if (typeof window.hljs === 'undefined') {
                    return;
                }

                document.querySelectorAll('pre').forEach((preEl) => {
                    if (preEl.querySelector('code')) {
                        return;
                    }

                    const codeEl = document.createElement('code');
                    codeEl.textContent = preEl.textContent || '';
                    preEl.textContent = '';
                    preEl.appendChild(codeEl);
                });

                document.querySelectorAll('pre code').forEach((codeEl) => {
                    if (codeEl.dataset.highlighted === '1') {
                        return;
                    }

                    const legacyLang = pickLegacyLanguage(codeEl);
                    if (legacyLang && !hasExplicitLanguage(codeEl)) {
                        codeEl.classList.add(`language-${legacyLang}`);
                    }

                    const text = codeEl.textContent || '';
                    if (!hasExplicitLanguage(codeEl)) {
                        const detected = detectLanguage(text);
                        if (detected) {
                            codeEl.classList.add(`language-${detected}`);
                        }
                    }

                    window.hljs.highlightElement(codeEl);
                    codeEl.dataset.highlighted = '1';
                });
            };

            document.addEventListener('DOMContentLoaded', highlightCodeBlocks);
            window.addEventListener('load', highlightCodeBlocks);
            document.addEventListener('livewire:navigated', highlightCodeBlocks);
        })();
    </script>
@endpush

@section('title', $seoMeta->title)
@section('meta')
    <meta name="description" content="{{ $seoMeta->description }}">
    <meta name="keywords" content="{{ $seoMeta->keywords }}">
    <meta name="keyword" content="{{ $seoMeta->keywords }}">
    <meta name="twitter:title" content="{{ $seoMeta->title }}">
    <meta name="twitter:description" content="{{ $seoMeta->description }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta property="og:title" content="{{ $seoMeta->title }}">
    <meta property="og:description" content="{{ $seoMeta->description }}">
    <meta property="og:url" content="{{ $seoMeta->canonical }}">
    <meta property="og:site_name" content="{{ $seoMeta->siteName }}">
    <meta property="og:type" content="article">
    <meta property="og:locale" content="zh_TW">
    <meta property="og:image" content="{{ $seoMeta->ogImage }}">
    <meta property="article:author" content="{{ $seoMeta->articleAuthor }}">
    <meta property="article:modified_time" content="{{ $seoMeta->articleModifiedTime }}">
    <link rel="canonical" href="{{ $seoMeta->canonical }}">
@endsection

@section('content')
    <a href="{{ route('articles.index') }}">← Back to list</a>
    <h1>{{ $article->title }}</h1>
    <p class="meta">{{ $article->authorName }} • Updated {{ $article->updatedAt->format('Y-m-d H:i:s') }}</p>

    <article class="article-content">
        {!! $article->contentHtml !!}
    </article>

    <livewire:comment-panel :article-id="$article->id" />
@endsection
