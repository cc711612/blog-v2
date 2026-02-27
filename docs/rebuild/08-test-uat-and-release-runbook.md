# 測試、UAT 與上線 Runbook

## 測試層級

- Unit：服務層（文章、留言、搜尋）
- Integration：路由、middleware、DB 寫入流程
- E2E：首頁 -> 文章頁 -> 留言/按讚

## UAT 核心清單

1. 首頁分頁可點、可爬
2. 搜尋關鍵字結果正確
3. 文章內容（含 code block）桌機/手機一致
4. 留言數與實際留言筆數一致
5. Dark Mode 切換與記憶正常
6. sitemap 可下載且 URL 正確

## 上線前檢查

- DB backup 完成
- `.env` 與 secrets 設定完成
- queue/redis 可連線
- Search Console 與 Analytics 可觀測

## 發版步驟

1. 部署程式
2. 執行 migrate
3. 清 cache（config/route/view）
4. 重啟 queue workers
5. 健康檢查（首頁/文章頁/API）

## 回滾步驟

1. 切回前版程式
2. 還原資料庫快照
3. 清應用快取
4. 驗證核心流程
