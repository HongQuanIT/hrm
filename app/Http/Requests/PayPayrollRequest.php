<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayPayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'account_id' => ['required', 'exists:finance_accounts,id'],
            'occurred_on' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'account_id.required' => 'Vui lòng chọn quỹ chi lương.',
            'occurred_on.required' => 'Vui lòng chọn ngày chi.',
        ];
    }
}
