<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

/**
 * 應用時區為台灣時間（Asia/Taipei，+08:00）。
 */
class AppTimezoneTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_app_and_php_default_timezone_is_taipei(): void
    {
        $this->assertSame('Asia/Taipei', config('app.timezone'));
        $this->assertSame('Asia/Taipei', date_default_timezone_get());
    }

    public function test_now_and_carbon_are_utc_plus_8(): void
    {
        // 固定一個 UTC 時刻，驗證各種取得「現在」的方式都輸出 +8 牆鐘
        $instant = CarbonImmutable::parse('2026-11-09 09:30:00', 'UTC');
        Carbon::setTestNow($instant);
        CarbonImmutable::setTestNow($instant);

        foreach ([now(), Date::now(), Carbon::now(), CarbonImmutable::now()] as $now) {
            $this->assertSame('Asia/Taipei', $now->getTimezone()->getName());
            $this->assertSame(8 * 3600, $now->getOffset());
            $this->assertSame('2026-11-09 17:30:00', $now->format('Y-m-d H:i:s'));
            $this->assertSame('+08:00', $now->format('P'));
        }

        // 沒指定時區的字串以台灣時間解讀
        $this->assertSame('2026-11-09T01:00:00+00:00', Carbon::parse('2026-11-09 09:00:00')->utc()->toIso8601String());
        $this->assertSame('2026-11-09 17:30:00', date('Y-m-d H:i:s', $instant->getTimestamp()));
    }

    public function test_app_timezone_can_be_overridden_by_env(): void
    {
        $config = file_get_contents(base_path('config/app.php'));

        $this->assertStringContainsString("'timezone' => env('APP_TIMEZONE', 'Asia/Taipei')", $config);
    }
}
