<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActionItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $retro = $this->route('retro');

        return [
            'body'        => ['required', 'string', 'min:2', 'max:255'],
            'assignee_id' => [
                'nullable',
                Rule::exists('retro_user', 'user_id')->where('retro_id', $retro?->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'assignee_id.exists' => 'Ответственный должен быть участником ретро',
        ];
    }
}
