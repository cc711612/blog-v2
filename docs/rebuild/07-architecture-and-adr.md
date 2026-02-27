# 架構規劃與 ADR

## 建議架構

- Backend：Laravel（LTS）
- UI：Blade + Livewire（新版）
- DB：MySQL 8+
- Cache：Redis
- Queue：Redis + Horizon（可選）
- Search：先用 SQL LIKE，後續可升 Meilisearch
- 開發規範：SOLID + TDD + Module/Entity/Service/Repository + FormRequest/Resource（見 `docs/rebuild/10-engineering-standards.md`）

## 模組切分

- Content：articles, comments
- Identity：users, socials, user_social
- Engagement：likes, viewer_count（快取主導）
- SEO：meta, sitemap, redirects

## ADR-001：文章內容儲存格式

- 決策：保留 HTML 儲存，但採白名單 sanitize
- 原因：既有內容已大量 HTML，轉 markdown 成本高
- 代價：需維護 sanitizer 規則

## ADR-002：分頁策略

- 決策：可索引分頁 URL 為主，Load More 只做 UX 增強
- 原因：SEO crawl/內部連結更穩定

## ADR-003：Like/Viewer 資料來源

- 決策：短期 cache-first，長期補 DB 落地
- 原因：降低寫入負載，保留高流量彈性

## ADR-004：Dark Mode

- 決策：CSS token + `data-theme`，不使用雙份樣式檔
- 原因：維護成本較低，切換一致
