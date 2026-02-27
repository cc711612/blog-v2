<?php

namespace App\Http\Resources;

use App\Modules\Content\Domain\Entities\CommentEntity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var CommentEntity $comment */
        $comment = $this->resource;

        return [
            'id' => $comment->id,
            'article_id' => $comment->articleId,
            'author_name' => $comment->authorName,
            'content' => $comment->content,
            'created_at' => $comment->createdAt->toIso8601String(),
        ];
    }
}
