<?php

namespace Weap\Junction\Tests\TestSupport\Requests;

use Weap\Junction\Http\Requests\DefaultFormRequest;

class MediaPostFormRequest extends DefaultFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string'],
            '_media' => ['nullable', 'array'],
        ];
    }
}
