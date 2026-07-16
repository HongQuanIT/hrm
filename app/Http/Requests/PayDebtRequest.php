<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayDebtRequest extends FormRequest
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
            'account_id' => ['required', 'exists:finance_accounts,id'],
            'amount' => ['required', 'numeric'],
            'occurred_on' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
