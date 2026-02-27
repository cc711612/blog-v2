<section class="comment-panel">
    <h2>Comments</h2>
    <div class="comment-tools">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="搜尋留言" />
        <select wire:model.live="sort">
            <option value="latest">最新</option>
            <option value="oldest">最舊</option>
        </select>
    </div>

    <div class="comment-list">
        @forelse($comments as $comment)
            <article class="comment-item">
                <p>{{ $comment->content }}</p>
                <small>{{ $comment->authorName }} • {{ $comment->createdAt->format('Y-m-d H:i') }}</small>
            </article>
        @empty
            <p class="empty">No comments yet.</p>
        @endforelse
    </div>

    @if($isGuest)
        <p class="meta">請先登入</p>
    @else
        <form wire:submit="submit" class="comment-form">
            <label>
                Name
                <input type="text" wire:model.live="authorName" placeholder="你的顯示名稱" />
            </label>
            <label>
                Comment
                <textarea wire:model.live="content" rows="4" placeholder="分享你的想法..."></textarea>
            </label>
            @error('content') <p class="error">{{ $message }}</p> @enderror
            <button type="submit">Post Comment</button>
        </form>
    @endif
</section>
