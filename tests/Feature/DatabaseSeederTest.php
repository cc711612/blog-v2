<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_runs_on_empty_database_and_is_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, DB::table('users')->where('id', 32)->count());
        $this->assertSame(1, DB::table('socials')->where('social_type_value', 'google-roy-32')->count());
    }

    public function test_seeder_never_overwrites_existing_user_credentials(): void
    {
        DB::table('users')->insert([
            'id' => 32,
            'name' => 'Real Owner',
            'email' => 'real@example.com',
            'password' => 'real-password-hash',
            'api_token' => 'real-token',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->seed(DatabaseSeeder::class);

        $user = DB::table('users')->where('id', 32)->first();
        $this->assertSame('real-password-hash', $user->password);
        $this->assertSame('real-token', $user->api_token);
        $this->assertSame('Real Owner', $user->name);
    }

    public function test_seeder_does_not_overwrite_existing_comments(): void
    {
        DB::table('users')->insert([
            'id' => 32, 'name' => 'Roy', 'email' => 'roy@example.com', 'password' => 'x',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('articles')->insert([
            'id' => 1, 'user_id' => 32, 'title' => 't', 'slug' => 't', 'content' => '<p>c</p>', 'status' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('comments')->insert([
            'id' => 1, 'article_id' => 1, 'user_id' => 32, 'content' => 'original', 'status' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame('original', DB::table('comments')->where('id', 1)->value('content'));
    }

    public function test_seeder_refuses_to_run_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('local development only');

        (new DatabaseSeeder)->run();
    }
}
