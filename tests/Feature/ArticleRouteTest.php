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

    public function test_page_beyond_last_page_returns_404_but_valid_pages_and_empty_search_stay_200(): void
    {
        DB::table('users')->insert([
            'id' => 32, 'name' => 'Roy', 'email' => 'roy@example.com', 'password' => Hash::make('password'),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('articles')->insert([
            'id' => 1, 'user_id' => 32, 'title' => 'Only one', 'slug' => 'only-one', 'content' => '<p>x</p>',
            'status' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->get('/article?page=5')->assertNotFound();
        $this->get('/article?page=1')->assertOk();
        $this->get('/article')->assertOk();
        $this->get('/article?search=nothing-matches-this')->assertOk();
    }

    public function test_home_with_no_articles_is_still_200(): void
    {
        $this->get('/')->assertOk();
        $this->get('/article?page=1')->assertOk();
    }
}
