<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCompanySettingsRequest;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    public function index()
    {
        $settings = CompanySetting::pairs();
        $departments = Department::with('head')->withCount('employees')->orderBy('name')->get();
        $users = User::orderByRaw("role = 'super_admin' DESC")->orderBy('name')->get();
        $employees = Employee::orderBy('name')->get(['id', 'name']);
        // F15: danh sách ngày nghỉ lễ để cấu hình.
        $holidays = Holiday::orderBy('date')->get();

        return view('settings.index', compact('settings', 'departments', 'users', 'employees', 'holidays'));
    }

    public function update(UpdateCompanySettingsRequest $request)
    {
        // F10: quy tắc kiểm tra tách sang UpdateCompanySettingsRequest.
        foreach ($request->validated() as $key => $value) {
            CompanySetting::put($key, $value);
        }

        return back()->with('status', 'Đã lưu thông tin công ty.');
    }

    public function storeHoliday(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'unique:holidays,date'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        Holiday::create($data);

        return back()->with('status', 'Đã thêm ngày nghỉ lễ.');
    }

    public function destroyHoliday(Holiday $holiday)
    {
        $holiday->delete();

        return back()->with('status', 'Đã xoá ngày nghỉ lễ.');
    }

    public function storeDepartment(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:departments,code'],
            'head_employee_id' => ['nullable', 'exists:employees,id'],
        ]);

        $data['head_name'] = $this->headName($data['head_employee_id'] ?? null);

        Department::create($data);

        return back()->with('status', 'Đã thêm phòng ban mới.');
    }

    public function updateDepartment(Request $request, Department $department)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('departments', 'code')->ignore($department->id)],
            'head_employee_id' => ['nullable', 'exists:employees,id'],
        ]);

        $data['head_name'] = $this->headName($data['head_employee_id'] ?? null);

        $department->update($data);

        return back()->with('status', 'Đã cập nhật phòng ban ' . $department->name . '.');
    }

    public function destroyDepartment(Department $department)
    {
        $department->delete();

        return back()->with('status', 'Đã xóa phòng ban.');
    }

    private function headName(?int $employeeId): ?string
    {
        return $employeeId ? Employee::find($employeeId)?->name : null;
    }

    public function updateUserRole(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => ['required', Rule::in([User::ROLE_SUPER_ADMIN, User::ROLE_USER])],
        ]);

        // Không cho phép tự hạ quyền chính mình để tránh khoá mất quyền quản trị.
        if ($user->id === $request->user()->id && $data['role'] !== User::ROLE_SUPER_ADMIN) {
            return back()->with('error', 'Bạn không thể tự gỡ quyền Super Admin của chính mình.');
        }

        // Đảm bảo hệ thống luôn còn ít nhất một Super Admin.
        if ($user->isSuperAdmin() && $data['role'] !== User::ROLE_SUPER_ADMIN
            && User::where('role', User::ROLE_SUPER_ADMIN)->count() <= 1) {
            return back()->with('error', 'Phải có ít nhất một Super Admin trong hệ thống.');
        }

        // F11: gán role qua property (role không nằm trong fillable).
        $user->role = $data['role'];
        $user->save();

        return back()->with('status', 'Đã cập nhật vai trò cho ' . $user->name . '.');
    }
}
