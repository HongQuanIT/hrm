<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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

    public function store(StoreEmployeeRequest $request)
    {
        $data = $request->validated();
        $password = $data['password'] ?? null;
        unset($data['password']);
        $data['skills'] = $this->parseSkills($request->input('skills'));

        // F06: không dùng mật khẩu mặc định "password". Nếu admin không đặt mật khẩu,
        // sinh mật khẩu tạm ngẫu nhiên và buộc nhân viên đổi khi đăng nhập lần đầu.
        $generatedPassword = null;

        $employee = DB::transaction(function () use (&$generatedPassword, $data, $password) {
            $user = User::where('email', $data['email'])->first();
            if (! $user) {
                $plain = $password ?: ($generatedPassword = Str::password(12));
                $user = new User(['name' => $data['name'], 'email' => $data['email']]);
                $user->password = Hash::make($plain);
                $user->role = User::ROLE_USER; // F11: gán role qua property, không mass-assign.
                $user->email_verified_at = now();
                $user->must_change_password = ($password === null);
                $user->save();
            }

            $data['user_id'] = $user->id;

            return Employee::create($data);
        });

        $passwordNote = $generatedPassword
            ? 'mật khẩu tạm "' . $generatedPassword . '" — nhân viên bắt buộc đổi khi đăng nhập lần đầu'
            : 'mật khẩu do bạn đặt';

        return redirect()->route('employees.index')->with(
            'status',
            'Đã thêm nhân viên và tạo tài khoản đăng nhập (email: ' . $employee->email . ', ' . $passwordNote . ').'
        );
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

    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        $data = $request->validated();
        $password = $data['password'] ?? null;
        unset($data['password']);
        $data['skills'] = $this->parseSkills($request->input('skills'));

        $generatedPassword = null;

        DB::transaction(function () use (&$generatedPassword, $employee, $data, $password) {
            // Đồng bộ tài khoản đăng nhập liên kết (tạo nếu nhân viên chưa có).
            $user = $employee->user ?? new User();
            $user->name = $data['name'];
            $user->email = $data['email'];
            if (! $user->exists) {
                // F06: tài khoản mới không có mật khẩu do admin đặt ⇒ dùng mật khẩu tạm + buộc đổi.
                $plain = $password ?: ($generatedPassword = Str::password(12));
                $user->password = Hash::make($plain);
                $user->role = User::ROLE_USER; // F11: gán role qua property.
                $user->email_verified_at = now();
                $user->must_change_password = ($password === null);
            } elseif ($password) {
                // Super Admin đặt lại mật khẩu cho tài khoản nhân viên (không buộc đổi tiếp).
                $user->password = Hash::make($password);
                $user->must_change_password = false;
            }
            $user->save();

            $data['user_id'] = $user->id;
            $employee->update($data);
        });

        $note = $generatedPassword
            ? ' Mật khẩu tạm: "' . $generatedPassword . '" (nhân viên bắt buộc đổi khi đăng nhập lần đầu).'
            : '';

        return redirect()->route('employees.show', $employee)->with('status', 'Đã cập nhật hồ sơ nhân viên và tài khoản liên kết.' . $note);
    }

    public function destroy(Employee $employee)
    {
        $user = $employee->user;

        // Tránh tự khoá tài khoản đang đăng nhập.
        if ($user && $user->id === auth()->id()) {
            return back()->with('error', 'Bạn không thể xoá nhân viên gắn với tài khoản đang đăng nhập.');
        }

        // Đảm bảo hệ thống luôn còn ít nhất một Super Admin.
        if ($user && $user->isSuperAdmin() && User::where('role', User::ROLE_SUPER_ADMIN)->count() <= 1) {
            return back()->with('error', 'Không thể xoá: đây là Super Admin duy nhất của hệ thống.');
        }

        DB::transaction(function () use ($employee, $user) {
            $employee->delete();
            $user?->delete();
        });

        return redirect()->route('employees.index')->with('status', 'Đã xóa nhân viên và tài khoản đăng nhập liên kết.');
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
