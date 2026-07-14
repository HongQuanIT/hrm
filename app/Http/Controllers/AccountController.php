<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function edit()
    {
        return view('account.edit');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ], [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'current_password.current_password' => 'Mật khẩu hiện tại không đúng.',
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.confirmed' => 'Xác nhận mật khẩu mới không khớp.',
        ]);

        // F06: đổi mật khẩu xong thì gỡ cờ buộc đổi (nếu có).
        $user = $request->user();
        $user->password = Hash::make($request->input('password'));
        $user->must_change_password = false;
        $user->save();

        return redirect()->route('account.edit')->with('status', 'Đã đổi mật khẩu thành công.');
    }

    /**
     * F14: trang tự cập nhật hồ sơ cá nhân (chỉ các trường an toàn).
     */
    public function editProfile(Request $request)
    {
        $employee = $this->currentEmployee($request);

        return view('account.profile', compact('employee'));
    }

    public function updateProfile(Request $request)
    {
        $employee = $this->currentEmployee($request);

        if (! $employee) {
            return back()->with('error', 'Tài khoản của bạn chưa gắn với hồ sơ nhân viên.');
        }

        // Chỉ cho phép nhân viên tự sửa các trường thông tin liên hệ, KHÔNG gồm
        // lương, phòng ban, chức danh, vai trò... (do Super Admin quản lý).
        $data = $request->validate([
            'phone' => ['nullable', 'string', 'max:50'],
            'personal_email' => ['nullable', 'email', 'max:255'],
            'permanent_address' => ['nullable', 'string', 'max:255'],
            'temporary_address' => ['nullable', 'string', 'max:255'],
            'emergency_contact' => ['nullable', 'string', 'max:255'],
        ]);

        $employee->update($data);

        return redirect()->route('account.profile')->with('status', 'Đã cập nhật thông tin cá nhân.');
    }

    private function currentEmployee(Request $request): ?Employee
    {
        $user = $request->user();

        return $user->employee ?? Employee::where('email', $user->email)->first();
    }
}
