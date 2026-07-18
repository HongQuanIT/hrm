<?php

namespace App\Http\Requests;

use App\Models\PayrollPeriod;
use Illuminate\Foundation\Http\FormRequest;

class StorePayrollPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2020,2100'],
            'days_in_month' => ['nullable', 'integer', 'between:28,31'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $exists = PayrollPeriod::where('month', $this->integer('month'))
                ->where('year', $this->integer('year'))
                ->exists();

            if ($exists) {
                $validator->errors()->add('month', 'Kỳ lương tháng này đã tồn tại.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'month.required' => 'Vui lòng chọn tháng.',
            'year.required' => 'Vui lòng nhập năm.',
        ];
    }
}
