<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) data_get($this->resource, 'id'),
            'title' => (string) data_get($this->resource, 'title'),
            'content_preview' => (string) data_get($this->resource, 'content_preview', ''),
            'author_name' => (string) data_get($this->resource, 'author_name', 'Unknown'),
            'created_at' => (string) data_get($this->resource, 'created_at'),
            'updated_at' => (string) data_get($this->resource, 'updated_at'),
            'seo' => data_get($this->resource, 'seo', []),
            'comments_count' => (int) data_get($this->resource, 'comments_count', 0),
            'url' => route('articles.show', ['id' => (int) data_get($this->resource, 'id')]),
        ];
    }
}
