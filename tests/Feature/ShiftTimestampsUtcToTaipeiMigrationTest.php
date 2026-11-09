<?php

namespace Tests\Feature;

use Illuminate\Database\MySqlConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * 2026_11_09_000000_shift_timestamps_utc_to_taipei：只把 blog-v2 寫入的 UTC 牆鐘（>= CUTOFF_UTC）+8 小時，
 * 舊 blog 寫的台灣時間（< CUTOFF_UTC）不動。
 * 測試跑在 SQLite（沒有 INTERVAL），migration 依 driver 產生 datetime(col, '+8 hours')；
 * MySQL 版的 SQL 另以字串比對驗證（實際執行另在 mysql:8.0 容器驗證，見 wt-blog-tz-notes.md）。
 */
class ShiftTimestampsUtcToTaipeiMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = '2026_11_09_000000_shift_timestamps_utc_to_taipei';

    private const ENV = 'BLOG_TZ_SHIFT_CONFIRMED';

    private const CUTOFF = '2026-10-13 13:17:46';

    protected function tearDown(): void
    {
        $this->setConfirm(null);
        parent::tearDown();
    }

    private function migration(): object
    {
        return require database_path('migrations/'.self::MIGRATION.'.php');
    }

    private function setConfirm(?string $value): void
    {
        if ($value === null) {
            unset($_ENV[self::ENV], $_SERVER[self::ENV]);
            putenv(self::ENV);

            return;
        }
        $_ENV[self::ENV] = $_SERVER[self::ENV] = $value;
        putenv(self::ENV.'='.$value);
    }

    private function article(int $id, ?string $created, ?string $updated, ?string $deleted): void
    {
        DB::table('articles')->insert([
            'id' => $id, 'user_id' => 1, 'title' => "A{$id}", 'slug' => "a-{$id}", 'content' => '<p>x</p>', 'status' => 1,
            'created_at' => $created, 'updated_at' => $updated, 'deleted_at' => $deleted,
        ]);
    }

    private function seedRows(): void
    {
        // 舊 blog（+8）：全部 < CUTOFF，不可動
        DB::table('users')->insert([
            'id' => 1, 'name' => 'Roy', 'email' => 'roy@example.com', 'password' => 'x',
            'email_verified_at' => null,
            'created_at' => '2021-08-08 16:26:27', 'updated_at' => '2026-02-27 23:01:04', 'deleted_at' => null,
        ]);
        // blog-v2（UTC）：全部 >= CUTOFF
        $this->article(1, '2026-10-16 10:09:49', '2026-10-17 13:43:43', null);
        // NULL 與舊值
        $this->article(2, null, null, '2022-06-17 01:14:43');
        // 同一列：created_at 舊（+8）、updated_at 新（UTC）
        $this->article(3, '2022-03-14 23:00:41', '2026-10-15 14:52:34', null);
        // 邊界：剛好 = CUTOFF（轉）、CUTOFF − 1 秒（不轉）、落在 [CUTOFF, CUTOFF+8h) 的最後一秒（轉）
        $this->article(4, self::CUTOFF, '2026-10-13 13:17:45', '2026-10-13 21:17:45');
        // 邊界：剛好 = CUTOFF + 8h（轉，down 時也必須正確還原）、CUTOFF + 8h − 1 秒前後
        $this->article(5, '2026-10-13 21:17:46', '2026-10-14 05:17:45', '2026-10-14 05:17:46');
        DB::table('failed_jobs')->insert([
            'uuid' => 'u-1', 'connection' => 'database', 'queue' => 'default', 'payload' => '{}', 'exception' => 'e',
            'failed_at' => '2022-03-13 09:35:08',
        ]);
    }

    /** @return array<string, mixed> */
    private function snapshot(): array
    {
        $snapshot = [
            'users' => (array) DB::table('users')->where('id', 1)->first(['email_verified_at', 'created_at', 'updated_at', 'deleted_at']),
        ];
        foreach (DB::table('articles')->orderBy('id')->get(['id', 'created_at', 'updated_at', 'deleted_at']) as $row) {
            $snapshot['a'.$row->id] = ['created_at' => $row->created_at, 'updated_at' => $row->updated_at, 'deleted_at' => $row->deleted_at];
        }
        $snapshot['failed'] = DB::table('failed_jobs')->value('failed_at');

        return $snapshot;
    }

    public function test_up_shifts_only_values_at_or_after_cutoff_and_keeps_nulls(): void
    {
        $this->seedRows();
        $this->setConfirm('1');

        $this->migration()->up();

        $this->assertSame([
            // 舊資料完全不動
            'users' => ['email_verified_at' => null, 'created_at' => '2021-08-08 16:26:27', 'updated_at' => '2026-02-27 23:01:04', 'deleted_at' => null],
            // 新資料 +8
            'a1' => ['created_at' => '2026-10-16 18:09:49', 'updated_at' => '2026-10-17 21:43:43', 'deleted_at' => null],
            // NULL 保持 NULL，舊 deleted_at 不動
            'a2' => ['created_at' => null, 'updated_at' => null, 'deleted_at' => '2022-06-17 01:14:43'],
            // 同一列各欄獨立判斷
            'a3' => ['created_at' => '2022-03-14 23:00:41', 'updated_at' => '2026-10-15 22:52:34', 'deleted_at' => null],
            'a4' => ['created_at' => '2026-10-13 21:17:46', 'updated_at' => '2026-10-13 13:17:45', 'deleted_at' => '2026-10-14 05:17:45'],
            'a5' => ['created_at' => '2026-10-14 05:17:46', 'updated_at' => '2026-10-14 13:17:45', 'deleted_at' => '2026-10-14 13:17:46'],
            'failed' => '2022-03-13 09:35:08',
        ], $this->snapshot());
    }

    public function test_down_exactly_reverts_up_including_boundary_values(): void
    {
        $this->seedRows();
        $before = $this->snapshot();
        $this->setConfirm('1');

        $migration = $this->migration();
        $migration->up();
        $this->assertNotSame($before, $this->snapshot());
        $migration->down();

        $this->assertSame($before, $this->snapshot());
    }

    public function test_up_then_down_is_identity_for_every_second_around_both_thresholds(): void
    {
        // 逐秒掃 CUTOFF±3 秒、CUTOFF+8h±3 秒，以及 CUTOFF−8h 附近；每個值放在 created_at（其餘欄 NULL）
        $base = new \DateTimeImmutable(self::CUTOFF, new \DateTimeZone('UTC'));
        $values = [];
        foreach ([-8 * 3600, 0, 8 * 3600, 16 * 3600] as $offset) {
            for ($d = -3; $d <= 3; $d++) {
                $values[] = $base->modify(sprintf('%+d seconds', $offset + $d))->format('Y-m-d H:i:s');
            }
        }
        DB::table('users')->insert(['id' => 1, 'name' => 'R', 'email' => 'r@example.com', 'password' => 'x']);
        foreach ($values as $i => $value) {
            $this->article(100 + $i, $value, null, null);
        }
        $before = $this->snapshot();
        $this->setConfirm('1');

        $migration = $this->migration();
        $migration->up();
        foreach (DB::table('articles')->orderBy('id')->pluck('created_at', 'id') as $id => $after) {
            $original = $values[$id - 100];
            $expected = $original >= self::CUTOFF
                ? (new \DateTimeImmutable($original, new \DateTimeZone('UTC')))->modify('+8 hours')->format('Y-m-d H:i:s')
                : $original;
            $this->assertSame($expected, $after, "up() for {$original}");
        }
        $migration->down();

        $this->assertSame($before, $this->snapshot());
    }

    public function test_refuses_without_confirmation_and_leaves_data_untouched(): void
    {
        $this->seedRows();
        $before = $this->snapshot();

        foreach ([null, '0', 'yes', '', 'true', 'TRUE', '(true)', ' 1', '01'] as $value) {
            $this->setConfirm($value);
            try {
                $this->migration()->up();
                $this->fail('migration should refuse without '.self::ENV.'=1 (value: '.var_export($value, true).')');
            } catch (RuntimeException $e) {
                $this->assertStringContainsString(self::ENV.'=1', $e->getMessage());
            }
        }

        $this->setConfirm(null);
        try {
            $this->migration()->down();
            $this->fail('down() should also refuse without confirmation');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString(self::ENV.'=1', $e->getMessage());
        }

        $this->assertSame($before, $this->snapshot());
    }

    public function test_artisan_migrate_fails_and_does_not_record_migration_without_confirmation(): void
    {
        // 模擬「branch 已合併、prod 尚未 cutover」：DB 有資料、本 migration 尚未執行
        DB::table('migrations')->where('migration', self::MIGRATION)->delete();
        $this->seedRows();
        $before = $this->snapshot();

        try {
            Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]);
            $this->fail('artisan migrate should fail without '.self::ENV);
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('拒絕執行', $e->getMessage());
        }

        $this->assertFalse(DB::table('migrations')->where('migration', self::MIGRATION)->exists());
        $this->assertSame($before, $this->snapshot());

        // 明確開啟後才會執行並記錄
        $this->setConfirm('1');
        Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]);
        $this->assertTrue(DB::table('migrations')->where('migration', self::MIGRATION)->exists());
        $this->assertSame('2026-10-16 18:09:49', DB::table('articles')->where('id', 1)->value('created_at'));
        $this->assertSame('2026-02-27 23:01:04', DB::table('users')->where('id', 1)->value('updated_at'));
    }

    public function test_empty_database_is_a_noop_without_confirmation(): void
    {
        // RefreshDatabase 已在空 DB 上跑過全部 migration（含本檔）；再跑一次 up/down 也不需要開關
        $this->assertTrue(DB::table('migrations')->where('migration', self::MIGRATION)->exists());
        $this->migration()->up();
        $this->migration()->down();
        $this->assertSame(0, DB::table('articles')->count());
    }

    public function test_mysql_sql_uses_case_per_column_and_sets_all_columns_in_one_statement(): void
    {
        // 不連線（PDO resolver 被呼叫就丟例外），只驗證產生的 SQL
        $mysql = new MySqlConnection(fn () => throw new RuntimeException('must not connect'), 'blog', '', ['driver' => 'mysql']);
        $migration = $this->migration();

        $this->assertSame(self::CUTOFF, $migration::CUTOFF_UTC);
        $this->assertSame(
            'UPDATE `articles` SET '
            ."`created_at` = CASE WHEN `created_at` >= '2026-10-13 13:17:46' THEN `created_at` + INTERVAL 8 HOUR ELSE `created_at` END, "
            ."`updated_at` = CASE WHEN `updated_at` >= '2026-10-13 13:17:46' THEN `updated_at` + INTERVAL 8 HOUR ELSE `updated_at` END, "
            ."`deleted_at` = CASE WHEN `deleted_at` >= '2026-10-13 13:17:46' THEN `deleted_at` + INTERVAL 8 HOUR ELSE `deleted_at` END "
            ."WHERE `created_at` >= '2026-10-13 13:17:46' OR `updated_at` >= '2026-10-13 13:17:46' OR `deleted_at` >= '2026-10-13 13:17:46'",
            $migration::updateSql($mysql, 'articles', ['created_at', 'updated_at', 'deleted_at'], 8),
        );
        // down 的門檻是 CUTOFF + 8h（up 的值域），不會碰到 < CUTOFF + 8h 的舊值
        $this->assertSame(
            "UPDATE `oauth_refresh_tokens` SET `expires_at` = CASE WHEN `expires_at` >= '2026-10-13 21:17:46' "
            ."THEN `expires_at` - INTERVAL 8 HOUR ELSE `expires_at` END WHERE `expires_at` >= '2026-10-13 21:17:46'",
            $migration::updateSql($mysql, 'oauth_refresh_tokens', ['expires_at'], -8),
        );
    }

    public function test_column_list_matches_production_inventory(): void
    {
        // 2026-11-09 prod information_schema：blog DB 全部 timestamp/datetime 欄位（共 46 欄、22 張表）
        $columns = $this->migration()::COLUMNS;

        $this->assertCount(22, $columns);
        $this->assertSame(46, array_sum(array_map('count', $columns)));
        $this->assertSame(['created_at', 'updated_at', 'expires_at'], $columns['oauth_access_tokens']);
        $this->assertSame(['failed_at'], $columns['failed_jobs']);
        $this->assertArrayNotHasKey('jobs', $columns);
        $this->assertArrayNotHasKey('sessions', $columns);
    }
}
