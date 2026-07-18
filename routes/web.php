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
use App\Http\Controllers\Payroll\MyPayslipController;
use App\Http\Controllers\Payroll\PayrollPeriodController;
use App\Http\Controllers\Payroll\PayslipController;
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
    // Sửa / xoá một giai đoạn ngay trên trang chi tiết (drawer). Quyền kiểm tra trong controller.
    Route::patch('/kpi/{kpi}/giai-doan/{phase}', [KpiController::class, 'updatePhase'])
        ->scopeBindings()->name('kpis.phases.update');
    Route::delete('/kpi/{kpi}/giai-doan/{phase}', [KpiController::class, 'destroyPhase'])
        ->scopeBindings()->name('kpis.phases.destroy');
    // Tài liệu đính kèm KPI / giai đoạn. Thành viên dự án hoặc Super Admin (kiểm tra trong controller).
    Route::post('/kpi/{kpi}/tai-lieu', [KpiController::class, 'storeAttachment'])
        ->name('kpis.attachments.store');
    Route::delete('/kpi/{kpi}/tai-lieu/{attachment}', [KpiController::class, 'destroyAttachment'])
        ->name('kpis.attachments.destroy');

    // Checklist của giai đoạn (assignee hoặc admin, kiểm tra trong controller).
    Route::post('/kpi/{kpi}/giai-doan/{phase}/checklist', [KpiController::class, 'addChecklistItem'])
        ->scopeBindings()->name('kpis.phases.checklist.store');
    // Không dùng scopeBindings ở đây vì quan hệ tên là `checklistItems` (Laravel sẽ đoán nhầm `items`);
    // quyền sở hữu item↔phase đã được kiểm tra thủ công trong controller.
    Route::patch('/kpi/{kpi}/giai-doan/{phase}/checklist/{item}', [KpiController::class, 'toggleChecklistItem'])
        ->name('kpis.phases.checklist.toggle');
    Route::delete('/kpi/{kpi}/giai-doan/{phase}/checklist/{item}', [KpiController::class, 'deleteChecklistItem'])
        ->name('kpis.phases.checklist.destroy');
    // Bình luận giai đoạn (mọi thành viên KPI).
    Route::post('/kpi/{kpi}/giai-doan/{phase}/binh-luan', [KpiController::class, 'addComment'])
        ->scopeBindings()->name('kpis.phases.comments.store');

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

    // M11 — Lương/Bảng lương.
    // Self-service: nhân viên xem phiếu của mình (khai báo trước để không bị "/luong/{period}" nuốt).
    Route::get('/luong/cua-toi', [MyPayslipController::class, 'index'])->name('payroll.my');
    // Xem chi tiết phiếu: admin xem tất cả, nhân viên xem phiếu của mình (kiểm tra trong controller).
    Route::get('/luong/{period}/phieu/{payslip}', [PayslipController::class, 'show'])
        ->scopeBindings()->name('payroll.payslips.show');

    // Quản trị kỳ lương: chỉ Super Admin.
    Route::middleware('can:admin')->prefix('luong')->name('payroll.')->group(function () {
        Route::get('/', [PayrollPeriodController::class, 'index'])->name('periods.index');
        Route::post('/', [PayrollPeriodController::class, 'store'])->name('periods.store');
        Route::get('/{period}', [PayrollPeriodController::class, 'show'])->name('periods.show');
        Route::post('/{period}/tinh', [PayrollPeriodController::class, 'calculate'])->name('periods.calculate');
        Route::patch('/{period}/duyet', [PayrollPeriodController::class, 'approve'])->name('periods.approve');
        Route::patch('/{period}/mo-lai', [PayrollPeriodController::class, 'reopen'])->name('periods.reopen');
        Route::post('/{period}/chi', [PayrollPeriodController::class, 'pay'])->name('periods.pay');
        Route::delete('/{period}', [PayrollPeriodController::class, 'destroy'])->name('periods.destroy');

        // Khoản cộng/trừ tay trên từng phiếu.
        Route::post('/{period}/phieu/{payslip}/khoan', [PayslipController::class, 'storeItem'])
            ->scopeBindings()->name('payslips.items.store');
        Route::delete('/{period}/phieu/{payslip}/khoan/{item}', [PayslipController::class, 'destroyItem'])
            ->name('payslips.items.destroy');
    });
});
