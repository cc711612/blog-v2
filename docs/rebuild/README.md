# Rebuild 文件總覽

本資料夾是「重做新版 Laravel 專案」的交付文件集合，已依實作順序整理。

## 文件清單

1. `docs/rebuild/01-prd-mvp.md`：產品需求、MVP 範圍、里程碑
2. `docs/rebuild/02-user-flow-and-acceptance.md`：角色流程與驗收標準
3. `docs/rebuild/03-data-migration-spec.md`：資料表 mapping、資料清洗與回滾
4. `docs/rebuild/04-api-and-page-contracts.md`：API/頁面合約
5. `docs/rebuild/05-seo-and-content-strategy.md`：URL/SEO/Sitemap/Redirect 規劃
6. `docs/rebuild/06-ui-style-guide-and-dark-mode.md`：視覺風格與暗黑模式規格
7. `docs/rebuild/07-architecture-and-adr.md`：技術決策與模組切分
8. `docs/rebuild/08-test-uat-and-release-runbook.md`：測試、UAT、部署切換
9. `docs/rebuild/09-operations-and-monitoring.md`：監控、告警、維運 SOP
10. `docs/rebuild/erd-detailed.md`：詳細 ERD 與欄位字典
11. `docs/rebuild/10-engineering-standards.md`：SOLID/TDD/模組化與 API 分層規範

## 建議使用方式

- 先確認 `01`、`02`，鎖定範圍與驗收標準。
- 再確認 `10`（ERD）與 `03`（搬遷規格），避免開發到一半才改 schema。
- 最後以 `08`、`09` 做上線前 Gate。
