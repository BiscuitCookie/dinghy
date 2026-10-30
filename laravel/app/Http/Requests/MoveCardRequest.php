<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'retro_column_id' => ['required', 'exists:retro_columns,id'],
            'position'        => ['required', 'integer', 'min:0'],
        ];
    }
}
