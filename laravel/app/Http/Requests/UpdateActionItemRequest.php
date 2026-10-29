<?php

namespace App\Http\Requests;

use App\Models\ActionItem;
use Illuminate\Foundation\Http\FormRequest;

class UpdateActionItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:'.implode(',', ActionItem::STATUSES)],
        ];
    }
}
