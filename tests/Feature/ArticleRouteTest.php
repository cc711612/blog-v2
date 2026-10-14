<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ArticleRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_numeric_article_id_returns_not_found(): void
    {
        $this->get('/article/.git/config')->assertNotFound();
        $this->get('/article/abc')->assertNotFound();
    }

    public function test_numeric_article_id_still_resolves(): void
    {
        DB::table('users')->insert([
            'id' => 32,
            'name' => 'Roy',
            'email' => 'roy@example.com',
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('articles')->insert([
            'id' => 80,
            'user_id' => 32,
            'title' => 'Route article',
            'content' => '<p>route</p>',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('/article/80')->assertRedirect();
        $this->get('/article/999')->assertNotFound();
    }
}
