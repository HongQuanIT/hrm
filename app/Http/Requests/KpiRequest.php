<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KpiRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'owner_employee_id' => ['nullable', 'exists:employees,id'],
            'measure_type' => ['required', Rule::in(['percent', 'count', 'milestone'])],
            'unit' => ['nullable', 'string', 'max:50'],
            'target_value' => ['nullable', 'numeric'],
            'current_value' => ['nullable', 'numeric'],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high'])],
            'status' => ['required', Rule::in(['on_track', 'in_progress', 'behind', 'done'])],
            'deadline' => ['nullable', 'date'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => [
                'file', 'max:10240',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,csv,txt,jpg,jpeg,png,gif,webp,svg,zip,rar',
            ],
        ];
    }
}
