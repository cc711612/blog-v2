<?php

namespace App\Livewire;

use App\Modules\Content\Application\Services\AddCommentService;
use App\Modules\Content\Domain\Repositories\CommentRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;

class CommentPanel extends Component
{
    /** 由伺服器在 mount 決定，禁止 client 竄改（否則可列出任意文章／未發佈文章的留言）。 */
    #[Locked]
    public int $articleId;

    public string $authorName = '';

    public string $content = '';

    public string $search = '';

    public string $sort = 'latest';

    private const SORTS = ['latest', 'oldest'];

    /** 每位使用者每分鐘最多送出的留言數 */
    private const SUBMIT_LIMIT_PER_MINUTE = 5;

    protected array $rules = [
        'authorName' => ['nullable', 'string', 'max:50'],
        'content' => ['required', 'string', 'min:2', 'max:500'],
    ];

    public function mount(int $articleId): void
    {
        $this->articleId = $articleId;
    }

    public function submit(AddCommentService $service): void
    {
        if (! Auth::check()) {
            $this->addError('content', '請先登入再留言。');

            return;
        }

        $user = Auth::user();

        $throttleKey = 'comment-submit:'.($user?->getAuthIdentifier() ?? request()->ip());
        if (RateLimiter::tooManyAttempts($throttleKey, self::SUBMIT_LIMIT_PER_MINUTE)) {
            $this->addError('content', '留言太頻繁，請稍後再試。');

            return;
        }

        $this->validate();

        RateLimiter::hit($throttleKey, 60);

        $service->handle(
            $this->articleId,
            $this->authorName !== '' ? $this->authorName : (string) $user?->name,
            $this->content,
            $user?->id,
        );

        $this->content = '';
        $this->dispatch('comment-added');
    }

    public function updatedSort(): void
    {
        if (! in_array($this->sort, self::SORTS, true)) {
            $this->sort = 'latest';
        }
    }

    public function render(CommentRepositoryInterface $comments)
    {
        return view('livewire.comment-panel', [
            'comments' => $comments->listByArticle($this->articleId, $this->search, in_array($this->sort, self::SORTS, true) ? $this->sort : 'latest'),
            'isGuest' => Auth::guest(),
        ]);
    }
}
