<?php

namespace Tests\Feature\Api;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ContentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('users')->insert([
            'id' => 32,
            'name' => 'Roy',
            'email' => 'roy@example.com',
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Article::query()->create([
            'id' => 44,
            'user_id' => 32,
            'title' => 'Laravel API Contract',
            'content' => '<p>demo content</p>',
            'seo' => ['description' => 'Demo seo'],
            'status' => 1,
            'published_at' => now(),
        ]);
    }

    public function test_articles_api_returns_resource_shape(): void
    {
        $response = $this->getJson('/api/articles');

        $response->assertOk()->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'title',
                    'content_preview',
                    'author_name',
                    'created_at',
                    'updated_at',
                    'comments_count',
                    'url',
                ],
            ],
        ]);
    }

    public function test_comment_store_validates_and_creates_comment(): void
    {
        $user = User::query()->findOrFail(32);

        $invalid = $this->postJson('/api/comments', [
            'article_id' => 44,
            'content' => 'a',
        ]);
        $invalid->assertStatus(401);

        $invalidPayload = $this->actingAs($user)->postJson('/api/comments', [
            'article_id' => 44,
            'content' => 'a',
        ]);
        $invalidPayload->assertStatus(422);

        $valid = $this->actingAs($user)->postJson('/api/comments', [
            'article_id' => 44,
            'author_name' => 'Tester',
            'content' => 'Looks good',
        ]);

        $valid->assertStatus(201)
            ->assertJsonPath('data.article_id', 44)
            ->assertJsonPath('data.author_name', 'Tester')
            ->assertJsonPath('data.content', 'Looks good');
    }
}
