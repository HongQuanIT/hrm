<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Số dư mục tiêu; cho phép âm (BR-M10-03).
            'target_balance' => ['required', 'numeric'],
            'occurred_on' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
