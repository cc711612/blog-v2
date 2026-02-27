<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Modules\Content\Application\Services\AddCommentService;

class CommentController extends Controller
{
    public function __construct(private readonly AddCommentService $addComment)
    {
    }

    public function store(StoreCommentRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();
        $comment = $this->addComment->handle(
            (int) $validated['article_id'],
            (string) ($validated['author_name'] ?? $user?->name ?? 'Guest'),
            (string) $validated['content'],
            $user?->id,
        );

        return (new CommentResource($comment))->response()->setStatusCode(201);
    }
}
