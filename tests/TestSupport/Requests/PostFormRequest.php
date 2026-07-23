<?php

namespace Weap\Junction\Tests\TestSupport\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostFormRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string'],
            'body' => ['nullable', 'string'],
            'user_id' => ['nullable', 'integer'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
