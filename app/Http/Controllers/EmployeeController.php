<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with('department');

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('department')) {
            $query->where('department_id', $request->input('department'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $employees = $query->orderBy('code')->paginate(10)->withQueryString();
        $departments = Department::orderBy('name')->get();
        $total = Employee::count();

        return view('employees.index', compact('employees', 'departments', 'total'));
    }

    public function create()
    {
        $departments = Department::orderBy('name')->get();
        $managers = Employee::orderBy('name')->get();
        $nextCode = 'PP-' . str_pad((string) (Employee::max('id') + 1), 4, '0', STR_PAD_LEFT);

        return view('employees.create', compact('departments', 'managers', 'nextCode'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['skills'] = $this->parseSkills($request->input('skills'));

        Employee::create($data);

        return redirect()->route('employees.index')->with('status', 'Đã thêm nhân viên mới thành công.');
    }

    public function show(Employee $employee)
    {
        $employee->load('department', 'manager');

        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        $departments = Department::orderBy('name')->get();
        $managers = Employee::where('id', '!=', $employee->id)->orderBy('name')->get();

        return view('employees.edit', compact('employee', 'departments', 'managers'));
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $this->validateData($request, $employee->id);
        $data['skills'] = $this->parseSkills($request->input('skills'));

        $employee->update($data);

        return redirect()->route('employees.show', $employee)->with('status', 'Đã cập nhật hồ sơ nhân viên.');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();

        return redirect()->route('employees.index')->with('status', 'Đã xóa nhân viên.');
    }

    private function validateData(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('employees', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($id)],
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
        ]);
    }

    private function parseSkills($skills): array
    {
        if (is_array($skills)) {
            return array_values(array_filter($skills));
        }

        if (is_string($skills) && trim($skills) !== '') {
            return array_values(array_filter(array_map('trim', explode(',', $skills))));
        }

        return [];
    }
}
