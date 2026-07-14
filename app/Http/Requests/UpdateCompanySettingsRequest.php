<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanySettingsRequest extends FormRequest
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
            'company_name' => ['nullable', 'string', 'max:255'],
            'tax_code' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'leave_days_per_month' => ['nullable', 'integer', 'min:0', 'max:31'],
            'leave_days_per_year' => ['nullable', 'integer', 'min:0', 'max:365'],
            'work_start_time' => ['nullable', 'date_format:H:i'],
            'work_end_time' => ['nullable', 'date_format:H:i'],
            'checkin_open_time' => ['nullable', 'date_format:H:i', 'before:work_start_time'],
            'late_grace_minutes' => ['nullable', 'integer', 'min:0', 'max:120'],
            'late_level1_minutes' => ['nullable', 'integer', 'min:1', 'max:240'],
            'late_level2_minutes' => ['nullable', 'integer', 'min:1', 'max:480'],
            'checkin_deadline' => ['nullable', 'date_format:H:i'],
            'checkout_deadline' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'checkin_open_time.before' => 'Giờ mở check-in phải sớm hơn giờ bắt đầu làm việc.',
        ];
    }
}
