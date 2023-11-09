<?php

namespace Weap\Junction\Http\Controllers\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DefaultFormRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return method_exists($this->route()->controller, 'rules') ? $this->route()->controller->rules() : [];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return method_exists($this->route()->controller, 'messages') ? $this->route()->controller->messages() : [];
    }
}
