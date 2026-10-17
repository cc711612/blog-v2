<?php

namespace Tests\Feature;

use App\Livewire\CommentPanel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class CommentPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('users')->insert([
            'id' => 32, 'name' => 'Roy', 'email' => 'roy@example.com', 'password' => 'x',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach ([1 => 1, 2 => 0] as $id => $status) {
            DB::table('articles')->insert([
                'id' => $id, 'user_id' => 32, 'title' => 't'.$id, 'slug' => 't'.$id, 'content' => '<p>c</p>',
                'status' => $status, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        DB::table('comments')->insert([
            'id' => 1, 'article_id' => 2, 'user_id' => 32, 'content' => 'secret comment on unpublished article',
            'status' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_article_id_cannot_be_tampered_with_from_client(): void
    {
        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::test(CommentPanel::class, ['articleId' => 1])->set('articleId', 2);
    }

    public function test_panel_only_lists_comments_of_its_own_article(): void
    {
        Livewire::test(CommentPanel::class, ['articleId' => 1])->assertDontSee('secret comment');
    }

    public function test_invalid_sort_value_falls_back_to_latest(): void
    {
        Livewire::test(CommentPanel::class, ['articleId' => 1])
            ->set('sort', 'DROP TABLE comments')
            ->assertSet('sort', 'latest');
    }

    public function test_submit_is_rate_limited_per_user(): void
    {
        $user = User::query()->findOrFail(32);
        RateLimiter::clear('comment-submit:32');

        $component = Livewire::actingAs($user)->test(CommentPanel::class, ['articleId' => 1]);

        foreach (range(1, 5) as $i) {
            $component->set('content', 'comment number '.$i)->call('submit')->assertHasNoErrors();
        }

        $component->set('content', 'one too many')->call('submit')->assertHasErrors('content');
        $this->assertSame(5, DB::table('comments')->where('article_id', 1)->count());
    }
}
