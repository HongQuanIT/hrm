<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\KpiController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Tài khoản cá nhân: mọi người dùng đăng nhập đều đổi được mật khẩu của mình.
    Route::get('/tai-khoan', [AccountController::class, 'edit'])->name('account.edit');
    Route::put('/tai-khoan/mat-khau', [AccountController::class, 'updatePassword'])->name('account.password');

    // Ghi dữ liệu nhân viên: chỉ Super Admin.
    // Khai báo trước read-only để route literal (create) không bị "show/{employee}" nuốt mất.
    Route::resource('nhan-vien', EmployeeController::class)
        ->parameters(['nhan-vien' => 'employee'])
        ->names('employees')
        ->except(['index', 'show'])
        ->middleware('can:admin');

    // Read-only: mọi tài khoản đăng nhập
    Route::resource('nhan-vien', EmployeeController::class)
        ->parameters(['nhan-vien' => 'employee'])
        ->names('employees')
        ->only(['index', 'show']);

    // Chấm công: xem chung, self-service check-in/out
    Route::get('/cham-cong', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/cham-cong/check-in', [AttendanceController::class, 'checkin'])->name('attendance.checkin');
    Route::post('/cham-cong/check-out', [AttendanceController::class, 'checkout'])->name('attendance.checkout');

    // Nghỉ phép: xem + tự tạo đơn cho mọi người; duyệt/xoá chỉ Super Admin
    Route::get('/nghi-phep', [LeaveController::class, 'index'])->name('leaves.index');
    Route::get('/nghi-phep/lich', [LeaveController::class, 'calendar'])->name('leaves.calendar');
    Route::post('/nghi-phep', [LeaveController::class, 'store'])->name('leaves.store');
    // Tự huỷ đơn của chính mình đang chờ (kiểm tra ownership trong controller)
    Route::patch('/nghi-phep/{leave}/huy', [LeaveController::class, 'cancel'])->name('leaves.cancel');
    Route::middleware('can:admin')->group(function () {
        Route::patch('/nghi-phep/{leave}/trang-thai', [LeaveController::class, 'updateStatus'])->name('leaves.status');
        Route::delete('/nghi-phep/{leave}', [LeaveController::class, 'destroy'])->name('leaves.destroy');
    });

    // KPI: ghi dữ liệu chỉ Super Admin (khai báo trước read-only vì lý do route literal như trên)
    Route::resource('kpi', KpiController::class)
        ->parameters(['kpi' => 'kpi'])
        ->names('kpis')
        ->except(['index', 'show'])
        ->middleware('can:admin');
    Route::resource('kpi', KpiController::class)
        ->parameters(['kpi' => 'kpi'])
        ->names('kpis')
        ->only(['index', 'show']);

    // Cài đặt hệ thống: chỉ Super Admin
    Route::middleware('can:admin')->group(function () {
        Route::get('/cai-dat', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/cai-dat', [SettingController::class, 'update'])->name('settings.update');
        Route::post('/cai-dat/phong-ban', [SettingController::class, 'storeDepartment'])->name('settings.departments.store');
        Route::put('/cai-dat/phong-ban/{department}', [SettingController::class, 'updateDepartment'])->name('settings.departments.update');
        Route::delete('/cai-dat/phong-ban/{department}', [SettingController::class, 'destroyDepartment'])->name('settings.departments.destroy');
        Route::put('/cai-dat/nguoi-dung/{user}/vai-tro', [SettingController::class, 'updateUserRole'])->name('settings.users.role');
    });
});
