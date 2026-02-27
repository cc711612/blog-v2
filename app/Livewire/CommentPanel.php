<?php

namespace App\Livewire;

use App\Modules\Content\Application\Services\AddCommentService;
use App\Modules\Content\Domain\Repositories\CommentRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CommentPanel extends Component
{
    public int $articleId;
    public string $authorName = '';
    public string $content = '';
    public string $search = '';
    public string $sort = 'latest';

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
        if (!Auth::check()) {
            $this->addError('content', '請先登入再留言。');

            return;
        }

        $this->validate();

        $user = Auth::user();

        $service->handle(
            $this->articleId,
            $this->authorName !== '' ? $this->authorName : (string) $user?->name,
            $this->content,
            $user?->id,
        );

        $this->content = '';
        $this->dispatch('comment-added');
    }

    public function render(CommentRepositoryInterface $comments)
    {
        return view('livewire.comment-panel', [
            'comments' => $comments->listByArticle($this->articleId, $this->search, $this->sort),
            'isGuest' => Auth::guest(),
        ]);
    }
}
