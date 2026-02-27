<?php

namespace Tests;

use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('app.env') !== 'testing') {
            throw new \RuntimeException('Refusing to run tests outside testing environment.');
        }

        if (DB::connection()->getDriverName() !== 'sqlite') {
            throw new \RuntimeException('Refusing to run tests on non-sqlite connection.');
        }
    }
}
