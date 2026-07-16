<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFinanceAccountRequest extends FormRequest
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
        $accountId = $this->route('account')?->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('finance_accounts', 'name')->ignore($accountId)],
            'type' => ['required', Rule::in(['cash', 'bank'])],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'opening_balance' => ['required', 'numeric'],
            'is_active' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }
}
