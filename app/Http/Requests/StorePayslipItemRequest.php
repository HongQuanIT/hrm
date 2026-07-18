<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayslipItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['earning', 'deduction'])],
            'label' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'Vui lòng nhập diễn giải.',
            'amount.required' => 'Vui lòng nhập số tiền.',
            'amount.min' => 'Số tiền phải lớn hơn 0.',
        ];
    }
}
