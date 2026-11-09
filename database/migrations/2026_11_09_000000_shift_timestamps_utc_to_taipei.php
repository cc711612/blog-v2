<?php

/*
|==========================================================================
| ⚠️  警告：一次性資料轉換，只能在 UTC 版 blog-v2 停止寫入「之後」執行  ⚠️
|==========================================================================
|
| 背景：blog DB 由兩個程式先後寫入：
|   - 舊 blog（laravel-admin，2021 起）：寫的是台灣時間（+8）牆鐘。
|     使用者 2026-11-09 確認「舊資料存的就是 +8」→ 不可再動。
|   - blog-v2（config/app.php 原本 'timezone' => 'UTC'）：寫的是 UTC 牆鐘。
|     應用時區改成 Asia/Taipei 後，只有這部分要 +8 小時。
| 兩者以 CUTOFF_UTC 區分：欄位值 >= CUTOFF_UTC 視為 blog-v2 寫入（UTC），+8；
| 小於的（舊 blog，+8）不動。每個欄位各自判斷（同一列可能 created_at 是舊的、
| updated_at 是 blog-v2 後來改的）。CUTOFF 的證據見 CUTOFF_UTC 上方註解。
|
| 若 usongrat 上 docker 版 blog-v2（app 時區 UTC）仍在寫同一個 DB，本 migration
| 之後它寫入的資料又會是 UTC，資料就混雜、無法再分辨。所以：
|   1. 先停 usongrat docker 版 blog-v2（或確認它不再寫入 blog DB）
|   2. 備份 blog DB
|   3. 以 BLOG_TZ_SHIFT_CONFIRMED=1 部署（helm pre-upgrade hook 會跑 migrate）
|   4. 驗證後拿掉 BLOG_TZ_SHIFT_CONFIRMED
|
| 防誤跑：只要清單內任一資料表有資料，就必須設環境變數 BLOG_TZ_SHIFT_CONFIRMED=1，
| 否則丟例外 → migrate 失敗、不會記入 migrations 表 → helm hook 失敗、整個部署中止
| （新的 Asia/Taipei 程式也不會上線，app 時區與資料不會不一致）。
| 清單內資料表全空（全新環境、測試）時沒有東西要轉，直接 no-op 成功。
| down() 同樣需要 BLOG_TZ_SHIFT_CONFIRMED=1。
|
| up / down 互逆（對每個值都成立，不需要備份欄位）：令 C = CUTOFF_UTC，H = 8h
|   up  ：v >= C     → v + H；否則不動
|   down：v >= C + H → v − H；否則不動
|   - v 為 NULL：兩邊都不動（NULL 比較為 NULL → CASE 走 ELSE）。
|   - v <  C：up 不動；v < C < C+H，down 也不動 → v。
|   - v >= C：up 得 v+H >= C+H，down 減回 → v。（含 v = C、v ∈ [C, C+H) 的邊界值）
|   up 之後不會有任何值落在 [C, C+H)（舊值 < C、轉過的 >= C+H），所以 down 的門檻
|   C+H 不會誤減舊值。前提：up 與 down 之間沒有新寫入（新程式寫的台灣時間 >= C+H
|   會被 down 一起 −8；這跟原本「全表 ±8」版本的限制相同）。
|
| 交易：Laravel 只在 Postgres 會自動把 migration 包進交易（MySQL / SQLite grammar
| 的 supportsSchemaTransactions() 為 false），所以這裡自己用 DB::transaction()。
| 本 migration 只有 DML（UPDATE），沒有 DDL，不會觸發 MySQL 的隱式 commit；
| 資料表皆為 InnoDB，任一 UPDATE 失敗（例如 TIMESTAMP 超出 2038 上限，strict mode
| 會報錯）就整批 rollback。
|
| ON UPDATE CURRENT_TIMESTAMP：2026-11-09 唯讀檢查 prod（SHOW CREATE TABLE），
| 清單內沒有任何欄位帶 ON UPDATE；唯一的 DEFAULT CURRENT_TIMESTAMP 是
| failed_jobs.failed_at（只影響 INSERT）。即使未來有欄位加上 ON UPDATE，本
| migration 每張表只下「一個」UPDATE，同時明確指定該表所有時間欄位（不需轉換的
| 欄位也以 CASE ... ELSE col 指定回原值），MySQL 對有被明確指定的欄位不會再自動
| 改成 CURRENT_TIMESTAMP，所以不會有副作用。
|
| TIMESTAMP 欄位與字串門檻的比較、+/− INTERVAL 都以 session 時區（prod 為 SYSTEM=CST，
| +8，無 DST）的牆鐘進行，與 Laravel 寫入／讀出的牆鐘字串一致。
|
| 不處理的表（理由見 PR／wt-blog-tz-notes.md）：jobs（reserved_at/available_at/
| created_at 是 unix int）、sessions（last_activity 是 unix int，且正式環境
| session 在 redis）、cache / job_batches（prod 不存在；cache 在 redis）、
| migrations、user_social、telescope_entries_tags、telescope_monitoring（無時間欄位）。
*/

use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public const CONFIRM_ENV = 'BLOG_TZ_SHIFT_CONFIRMED';

    public const HOURS = 8;

    /**
     * blog-v2 第一次寫入 prod blog DB 的時刻（UTC 牆鐘，與 blog-v2 寫入的表示法相同）。
     *
     * 證據（2026-11-09 唯讀查證，細節見 wt-blog-tz-notes.md）：
     *  1. Jenkins blog-v2 build #4 console log：2026-10-13T13:17:46.055Z「Running migrations」，
     *     13:17:46.660Z 2026_02_28_000500_add_slug_to_articles_table DONE。
     *     這是 migrations 表 batch 15（id 32），唯一 blog-v2 專屬的 migration
     *     （batch 1–14 皆為舊 blog：admin 表資料 2022、telescope 資料 2025）。
     *  2. prod information_schema.tables：articles.create_time = 2026-10-13 21:17:46（+8）
     *     = 13:17:46 UTC（上述 ALTER 重建表）；其餘表 create_time 皆為 2026-07-02 匯入時間。
     *  3. 資料斷層：46 欄全部的值在 (2026-02-27 23:01:04, 2026-10-15 10:01:41) 之間筆數為 0；
     *     之後的值（articles 81–89 建立、批次更新 14:52:34 / 15:38:43 / 16:02:37 / 03:09:02 /
     *     15:32:45）與 /root/config-backups 對應備份檔的 mtime（UTC）逐秒吻合 → 確為 UTC 牆鐘。
     * 不確定區間：舊 blog 在 CUTOFF 前 8 小時內寫入的 +8 值會落在 [CUTOFF, CUTOFF+8h) 而被誤判；
     * prod 該區間（以及 [CUTOFF−8h, CUTOFF)）46 欄皆 0 筆，且整個斷層內也是 0 筆，
     * 所以 CUTOFF 取斷層內任何時刻，轉換結果都相同。
     */
    public const CUTOFF_UTC = '2026-10-13 13:17:46';

    /**
     * 要轉換的資料表與欄位（2026-11-09 由 prod information_schema 列出，刻意寫死不動態查詢）。
     * 正式環境有、但本 repo 沒有建表 migration 的表（oauth_*、personal_access_tokens、
     * password_resets）在測試 / 新環境不存在，會被略過。
     *
     * @var array<string, list<string>>
     */
    public const COLUMNS = [
        'admin_menu' => ['created_at', 'updated_at'],
        'admin_operation_log' => ['created_at', 'updated_at'],
        'admin_permissions' => ['created_at', 'updated_at'],
        'admin_role_menu' => ['created_at', 'updated_at'],
        'admin_role_permissions' => ['created_at', 'updated_at'],
        'admin_role_users' => ['created_at', 'updated_at'],
        'admin_roles' => ['created_at', 'updated_at'],
        'admin_user_permissions' => ['created_at', 'updated_at'],
        'admin_users' => ['created_at', 'updated_at'],
        'articles' => ['created_at', 'updated_at', 'deleted_at'],
        'comments' => ['created_at', 'updated_at', 'deleted_at'],
        'failed_jobs' => ['failed_at'],
        'oauth_access_tokens' => ['created_at', 'updated_at', 'expires_at'],
        'oauth_auth_codes' => ['expires_at'],
        'oauth_clients' => ['created_at', 'updated_at'],
        'oauth_personal_access_clients' => ['created_at', 'updated_at'],
        'oauth_refresh_tokens' => ['expires_at'],
        'password_resets' => ['created_at'],
        'personal_access_tokens' => ['last_used_at', 'created_at', 'updated_at'],
        'socials' => ['created_at', 'updated_at', 'deleted_at'],
        'telescope_entries' => ['created_at'],
        'users' => ['email_verified_at', 'created_at', 'updated_at', 'deleted_at'],
    ];

    public function up(): void
    {
        $this->shift(+self::HOURS);
    }

    public function down(): void
    {
        $this->shift(-self::HOURS);
    }

    private function shift(int $hours): void
    {
        $connection = DB::connection($this->getConnection());
        $tables = array_filter(
            self::COLUMNS,
            fn (string $table): bool => Schema::connection($this->getConnection())->hasTable($table),
            ARRAY_FILTER_USE_KEY,
        );

        // 全新環境（或測試）沒有任何資料可轉：no-op。
        $hasData = false;
        foreach (array_keys($tables) as $table) {
            if ($connection->table($table)->exists()) {
                $hasData = true;
                break;
            }
        }
        if (! $hasData) {
            return;
        }

        // 讀原始字串：env() 會把 true/(true) 轉成 bool，(string) true === '1'，等於放寬了開關
        if (Env::getRepository()->get(self::CONFIRM_ENV) !== '1') {
            throw new RuntimeException(sprintf(
                '[shift_timestamps_utc_to_taipei] 拒絕執行：blog DB 有資料，但未設定 %s=1。'
                .'這個 migration 會把 blog-v2 寫入的時間值（>= %s）%+d 小時，只能在 UTC 版 blog-v2 停止寫入、'
                .'並備份 blog DB 之後，以 %s=1 明確執行。',
                self::CONFIRM_ENV, self::CUTOFF_UTC, $hours, self::CONFIRM_ENV,
            ));
        }

        $connection->transaction(function () use ($connection, $tables, $hours): void {
            foreach ($tables as $table => $columns) {
                $connection->update(self::updateSql($connection, $table, $columns, $hours));
            }
        });
    }

    /**
     * 門檻：up（+H）轉換 >= CUTOFF 的值；down（−H）只還原 >= CUTOFF + H 的值（up 的值域）。
     */
    public static function threshold(int $hours): string
    {
        $cutoff = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', self::CUTOFF_UTC, new DateTimeZone('UTC'));
        if ($cutoff === false || $cutoff->format('Y-m-d H:i:s') !== self::CUTOFF_UTC) {
            throw new RuntimeException('[shift_timestamps_utc_to_taipei] CUTOFF_UTC 格式錯誤：'.self::CUTOFF_UTC);
        }

        return $hours > 0
            ? self::CUTOFF_UTC
            : $cutoff->modify(sprintf('+%d hours', abs($hours)))->format('Y-m-d H:i:s');
    }

    /**
     * 每張表一個 UPDATE，同時指定該表全部時間欄位（避免 ON UPDATE CURRENT_TIMESTAMP 副作用），
     * 每欄以 CASE 各自判斷是否 >= 門檻；WHERE 只挑至少一欄 >= 門檻的列。
     *
     * @param  list<string>  $columns
     */
    public static function updateSql(Connection $connection, string $table, array $columns, int $hours): string
    {
        $grammar = $connection->getQueryGrammar();
        $driver = $connection->getDriverName();
        // 門檻是程式內常數、格式已驗證（只有數字、-、:、空白），直接內嵌字串安全
        $threshold = "'".self::threshold($hours)."'";

        $sets = [];
        $wheres = [];
        foreach ($columns as $column) {
            $col = $grammar->wrap($column);
            $shifted = match ($driver) {
                // MySQL：TIMESTAMP 以 session 時區（+8，無 DST）做牆鐘運算，DATETIME 直接運算
                'mysql', 'mariadb' => sprintf('%s %s INTERVAL %d HOUR', $col, $hours < 0 ? '-' : '+', abs($hours)),
                // SQLite（測試用）：沒有 INTERVAL 語法，用 datetime() modifier
                'sqlite' => sprintf("datetime(%s, '%+d hours')", $col, $hours),
                default => throw new RuntimeException("[shift_timestamps_utc_to_taipei] 不支援的 DB driver：{$driver}"),
            };
            // NULL >= x 為 NULL → 走 ELSE，NULL 保持 NULL
            $sets[] = sprintf('%1$s = CASE WHEN %1$s >= %2$s THEN %3$s ELSE %1$s END', $col, $threshold, $shifted);
            $wheres[] = sprintf('%s >= %s', $col, $threshold);
        }

        return sprintf(
            'UPDATE %s SET %s WHERE %s',
            $grammar->wrapTable($table),
            implode(', ', $sets),
            implode(' OR ', $wheres),
        );
    }
};
