<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    public function index()
    {
        $settings = CompanySetting::pairs();
        $departments = Department::withCount('employees')->orderBy('name')->get();
        $users = User::orderByRaw("role = 'super_admin' DESC")->orderBy('name')->get();

        return view('settings.index', compact('settings', 'departments', 'users'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'company_name' => ['nullable', 'string', 'max:255'],
            'tax_code' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'leave_days_per_month' => ['nullable', 'integer', 'min:0', 'max:31'],
            'leave_days_per_year' => ['nullable', 'integer', 'min:0', 'max:365'],
            // Giờ làm việc & chính sách chấm công
            'work_start_time' => ['nullable', 'date_format:H:i'],
            'work_end_time' => ['nullable', 'date_format:H:i'],
            'late_grace_minutes' => ['nullable', 'integer', 'min:0', 'max:120'],
            'late_level1_minutes' => ['nullable', 'integer', 'min:1', 'max:240'],
            'late_level2_minutes' => ['nullable', 'integer', 'min:1', 'max:480'],
            'checkin_deadline' => ['nullable', 'date_format:H:i'],
            'checkout_deadline' => ['nullable', 'date_format:H:i'],
        ]);

        foreach ($data as $key => $value) {
            CompanySetting::put($key, $value);
        }

        return back()->with('status', 'Đã lưu thông tin công ty.');
    }

    public function storeDepartment(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:departments,code'],
            'head_name' => ['nullable', 'string', 'max:255'],
        ]);

        Department::create($data);

        return back()->with('status', 'Đã thêm phòng ban mới.');
    }

    public function destroyDepartment(Department $department)
    {
        $department->delete();

        return back()->with('status', 'Đã xóa phòng ban.');
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

        $user->update(['role' => $data['role']]);

        return back()->with('status', 'Đã cập nhật vai trò cho ' . $user->name . '.');
    }
}
