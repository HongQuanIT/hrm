<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:50', Rule::unique('employees', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('employees', 'email')],
            'personal_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'dob' => ['nullable', 'date'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'marital_status' => ['nullable', 'string', 'max:50'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'permanent_address' => ['nullable', 'string', 'max:255'],
            'temporary_address' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'position' => ['nullable', 'string', 'max:150'],
            'level' => ['nullable', 'string', 'max:100'],
            'contract_type' => ['nullable', 'string', 'max:100'],
            'join_date' => ['nullable', 'date'],
            'manager_id' => ['nullable', 'exists:employees,id'],
            'status' => ['required', Rule::in(['active', 'on_leave', 'resigned'])],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account' => ['nullable', 'string', 'max:100'],
            'bank_holder' => ['nullable', 'string', 'max:255'],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'lunch_allowance' => ['nullable', 'numeric', 'min:0'],
            'emergency_contact' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:6'],
        ];
    }
}
