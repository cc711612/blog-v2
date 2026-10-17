<?php

namespace Tests\Unit;

use Tests\TestCase;

class TestEnvironmentGuardTest extends TestCase
{
    public function test_guard_passes_on_sqlite_testing_environment(): void
    {
        $this->guardAgainstUnsafeEnvironment($this->app);

        $this->assertSame('sqlite', config('database.connections.'.config('database.default').'.driver'));
    }

    public function test_guard_rejects_non_sqlite_connection(): void
    {
        config(['database.default' => 'mysql']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('non-sqlite');
        $this->guardAgainstUnsafeEnvironment($this->app);
    }

    public function test_guard_rejects_non_testing_environment(): void
    {
        config(['app.env' => 'production']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('outside testing environment');
        $this->guardAgainstUnsafeEnvironment($this->app);
    }
}
