<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreActionItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body'        => ['required', 'string', 'min:2', 'max:255'],
            'assignee_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
