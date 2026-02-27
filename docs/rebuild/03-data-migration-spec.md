# 資料遷移規格（現站 -> 新站）

## 範圍

- 主要資料：`users`, `articles`, `comments`, `socials`, `user_social`
- 來源：既有 MySQL（`blog`）

## Mapping（摘要）

- `users.id` -> `users.id`（保留）
- `articles.id` -> `articles.id`（建議保留，維持舊 URL）
- `comments.id` -> `comments.id`（建議保留）
- `socials` / `user_social` 依現有關聯搬遷

## 內容清洗規則（重點）

- 文章內容 `content`：
  - 程式碼區塊統一為 `<pre><code>...</code></pre>`
  - 移除危險 script/event attributes
  - 保留必要語意標籤（h1-h4/p/ul/ol/li/pre/code/a/img）
- SEO 欄位 `seo`（序列化）建議改 JSON 儲存
- `images`（序列化）建議改 JSON array 儲存

## 遷移步驟

1. 全庫備份（schema + data）
2. 先匯入 `users`
3. 再匯入 `articles`
4. 匯入 `comments`（檢查 FK）
5. 匯入 `socials`, `user_social`
6. 驗證筆數與抽樣內容

## 驗證清單

- 筆數比對：舊/新每表 count 一致
- 抽樣 20 篇文章：標題、內文、code block 顯示正常
- 抽樣留言：每篇 `comments_count` 與列表一致
- 404 掃描：舊文章 URL 不應失效

## 回滾計畫

- 保留上版 DB snapshot（至少 7 天）
- 回滾順序：
  1. 切回舊應用
  2. 還原舊 DB
  3. 清除新站 cache
  4. 驗證首頁、文章、留言
