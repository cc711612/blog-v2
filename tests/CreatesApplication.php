<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // 必須在 app 建立後、RefreshDatabase 執行 migrate:fresh 之前就檢查；
        // 放在 setUp() 的 parent::setUp() 之後就來不及了（資料表已被清掉）。
        $this->guardAgainstUnsafeEnvironment($app);

        return $app;
    }

    /**
     * 測試只允許在 testing 環境 + sqlite 連線上跑，否則在碰任何資料庫之前就中止。
     */
    protected function guardAgainstUnsafeEnvironment(Application $app): void
    {
        $config = $app->make('config');

        if ($config->get('app.env') !== 'testing') {
            throw new \RuntimeException('Refusing to run tests outside testing environment.');
        }

        $connection = $config->get('database.default');
        if ($config->get("database.connections.{$connection}.driver") !== 'sqlite') {
            throw new \RuntimeException('Refusing to run tests on non-sqlite connection.');
        }
    }
}
