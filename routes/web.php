<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\Finance\FinanceAccountController;
use App\Http\Controllers\Finance\FinanceCategoryController;
use App\Http\Controllers\Finance\FinanceDebtController;
use App\Http\Controllers\Finance\FinanceOverviewController;
use App\Http\Controllers\Finance\FinanceTransactionController;
use App\Http\Controllers\KpiController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    // F05: giới hạn số lần thử đăng nhập để chống dò mật khẩu (brute force).
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1')
        ->name('login.attempt');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Tài khoản cá nhân: mọi người dùng đăng nhập đều đổi được mật khẩu của mình.
    Route::get('/tai-khoan', [AccountController::class, 'edit'])->name('account.edit');
    Route::put('/tai-khoan/mat-khau', [AccountController::class, 'updatePassword'])->name('account.password');
    // F14: nhân viên tự cập nhật thông tin liên hệ cá nhân.
    Route::get('/tai-khoan/ho-so', [AccountController::class, 'editProfile'])->name('account.profile');
    Route::put('/tai-khoan/ho-so', [AccountController::class, 'updateProfile'])->name('account.profile.update');

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
    // Thành viên dự án (chủ trì / được giao giai đoạn) hoặc Super Admin thêm giai đoạn con.
    // Quyền được kiểm tra trong controller.
    Route::post('/kpi/{kpi}/giai-doan', [KpiController::class, 'storePhase'])
        ->name('kpis.phases.store');
    // Người phụ trách giai đoạn tự cập nhật trạng thái (nhận / đang làm / hoàn thành).
    // Quyền được kiểm tra trong controller: chỉ assignee của giai đoạn hoặc Super Admin.
    Route::patch('/kpi/{kpi}/giai-doan/{phase}/trang-thai', [KpiController::class, 'updatePhaseStatus'])
        ->scopeBindings()
        ->name('kpis.phases.status');

    // Cài đặt hệ thống: chỉ Super Admin
    Route::middleware('can:admin')->group(function () {
        Route::get('/cai-dat', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/cai-dat', [SettingController::class, 'update'])->name('settings.update');
        Route::post('/cai-dat/phong-ban', [SettingController::class, 'storeDepartment'])->name('settings.departments.store');
        Route::put('/cai-dat/phong-ban/{department}', [SettingController::class, 'updateDepartment'])->name('settings.departments.update');
        Route::delete('/cai-dat/phong-ban/{department}', [SettingController::class, 'destroyDepartment'])->name('settings.departments.destroy');
        Route::put('/cai-dat/nguoi-dung/{user}/vai-tro', [SettingController::class, 'updateUserRole'])->name('settings.users.role');
        // F15: quản lý ngày nghỉ lễ.
        Route::post('/cai-dat/ngay-le', [SettingController::class, 'storeHoliday'])->name('settings.holidays.store');
        Route::delete('/cai-dat/ngay-le/{holiday}', [SettingController::class, 'destroyHoliday'])->name('settings.holidays.destroy');
    });

    // M10 — Quản lý tài chính: chỉ Super Admin (dữ liệu nhạy cảm).
    Route::middleware('can:admin')->prefix('tai-chinh')->name('finance.')->group(function () {
        Route::get('/', [FinanceOverviewController::class, 'index'])->name('overview');

        // Quỹ tiền
        Route::get('/quy', [FinanceAccountController::class, 'index'])->name('accounts.index');
        Route::post('/quy', [FinanceAccountController::class, 'store'])->name('accounts.store');
        Route::put('/quy/{account}', [FinanceAccountController::class, 'update'])->name('accounts.update');
        Route::delete('/quy/{account}', [FinanceAccountController::class, 'destroy'])->name('accounts.destroy');
        Route::post('/quy/{account}/nap-tien', [FinanceAccountController::class, 'deposit'])->name('accounts.deposit');
        Route::post('/quy/{account}/dieu-chinh', [FinanceAccountController::class, 'adjust'])->name('accounts.adjust');

        // Danh mục thu/chi
        Route::get('/danh-muc', [FinanceCategoryController::class, 'index'])->name('categories.index');
        Route::post('/danh-muc', [FinanceCategoryController::class, 'store'])->name('categories.store');
        Route::put('/danh-muc/{category}', [FinanceCategoryController::class, 'update'])->name('categories.update');
        Route::delete('/danh-muc/{category}', [FinanceCategoryController::class, 'destroy'])->name('categories.destroy');

        // Sổ giao dịch thu/chi
        Route::get('/giao-dich', [FinanceTransactionController::class, 'index'])->name('transactions.index');
        Route::post('/giao-dich', [FinanceTransactionController::class, 'store'])->name('transactions.store');
        Route::put('/giao-dich/{transaction}', [FinanceTransactionController::class, 'update'])->name('transactions.update');
        Route::delete('/giao-dich/{transaction}', [FinanceTransactionController::class, 'destroy'])->name('transactions.destroy');

        // Công nợ
        Route::get('/cong-no', [FinanceDebtController::class, 'index'])->name('debts.index');
        Route::post('/cong-no', [FinanceDebtController::class, 'store'])->name('debts.store');
        Route::put('/cong-no/{debt}', [FinanceDebtController::class, 'update'])->name('debts.update');
        Route::post('/cong-no/{debt}/thanh-toan', [FinanceDebtController::class, 'pay'])->name('debts.pay');
        Route::patch('/cong-no/{debt}/huy', [FinanceDebtController::class, 'cancel'])->name('debts.cancel');
        Route::delete('/cong-no/{debt}', [FinanceDebtController::class, 'destroy'])->name('debts.destroy');
    });
});
