<?php

namespace App\Http\Requests;

use App\Support\RetroTemplates;
use Illuminate\Foundation\Http\FormRequest;

class StoreRetroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'          => ['required', 'string', 'max:255'],
            'template'       => ['required', 'in:'.implode(',', array_keys(RetroTemplates::TEMPLATES))],
            'is_anonymous'   => ['boolean'],
            'votes_per_user' => ['integer', 'min:1', 'max:10'],
        ];
    }

    public function validatedRetroData(): array
    {
        return [
            'title'          => $this->validated('title'),
            'template'       => $this->validated('template'),
            'is_anonymous'   => $this->boolean('is_anonymous'),
            'votes_per_user' => $this->validated('votes_per_user') ?? 3,
        ];
    }
}
