<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'article_id' => ['required', 'integer', 'exists:articles,id'],
            'author_name' => ['nullable', 'string', 'max:50'],
            'content' => ['required', 'string', 'min:2', 'max:500'],
        ];
    }
}
