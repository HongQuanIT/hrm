<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Kpi;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->seedSettings();
        $departments = $this->seedDepartments();
        [$admin, $employees] = $this->seedEmployees($departments);
        $this->seedUsers($admin, $employees);
        $this->linkDepartmentHeads($departments);
        $this->seedAttendances($employees);
        $this->seedLeaves($employees, $admin);
        $this->seedKpis($departments, $employees);
        $this->seedFinance($admin);
    }

    private function seedFinance(Employee $admin): void
    {
        $adminUserId = $admin->user_id ?? User::where('role', User::ROLE_SUPER_ADMIN)->value('id');

        // Quỹ tiền
        $cash = \App\Models\FinanceAccount::create([
            'name' => 'Tiền mặt',
            'type' => 'cash',
            'opening_balance' => 50000000,
            'note' => 'Quỹ tiền mặt văn phòng',
        ]);
        $bank = \App\Models\FinanceAccount::create([
            'name' => 'VCB — Tài khoản công ty',
            'type' => 'bank',
            'bank_name' => 'Vietcombank',
            'account_number' => '0123456789',
            'opening_balance' => 200000000,
        ]);

        // Danh mục
        $catSalary = \App\Models\FinanceCategory::create(['name' => 'Lương & thưởng', 'direction' => 'expense', 'color' => '#EF4444']);
        $catRent = \App\Models\FinanceCategory::create(['name' => 'Thuê văn phòng', 'direction' => 'expense', 'color' => '#F59E0B']);
        $catService = \App\Models\FinanceCategory::create(['name' => 'Doanh thu dịch vụ', 'direction' => 'income', 'color' => '#22C55E']);
        \App\Models\FinanceCategory::create(['name' => 'Tiện ích (điện/nước/internet)', 'direction' => 'expense', 'color' => '#3B82F6']);

        // Giao dịch mẫu
        $bank->transactions()->create([
            'category_id' => $catService->id, 'direction' => 'income', 'amount' => 80000000,
            'occurred_on' => Carbon::today()->subDays(20)->toDateString(),
            'description' => 'Thu tiền dự án A', 'created_by' => $adminUserId,
        ]);
        $bank->transactions()->create([
            'category_id' => $catRent->id, 'direction' => 'expense', 'amount' => 25000000,
            'occurred_on' => Carbon::today()->subDays(15)->toDateString(),
            'description' => 'Thuê văn phòng tháng này', 'created_by' => $adminUserId,
        ]);
        $cash->transactions()->create([
            'category_id' => $catSalary->id, 'direction' => 'expense', 'amount' => 45000000,
            'occurred_on' => Carbon::today()->subDays(5)->toDateString(),
            'description' => 'Tạm ứng lương', 'created_by' => $adminUserId,
        ]);
        $bank->transactions()->create([
            'direction' => 'income', 'amount' => 100000000, 'is_contribution' => true,
            'contributor_name' => 'Giám đốc góp vốn',
            'occurred_on' => Carbon::today()->subDays(25)->toDateString(),
            'description' => 'Góp thêm vốn kinh doanh', 'created_by' => $adminUserId,
        ]);

        // Công nợ
        \App\Models\FinanceDebt::create([
            'type' => 'receivable', 'partner_name' => 'Công ty TNHH Khách Hàng B',
            'amount' => 60000000, 'due_date' => Carbon::today()->addDays(10)->toDateString(),
            'status' => 'open', 'description' => 'Công nợ hợp đồng dịch vụ',
        ]);
        \App\Models\FinanceDebt::create([
            'type' => 'payable', 'partner_name' => 'Nhà cung cấp thiết bị C',
            'amount' => 30000000, 'due_date' => Carbon::today()->subDays(3)->toDateString(),
            'status' => 'overdue', 'description' => 'Mua máy tính',
        ]);
    }

    private function seedSettings(): void
    {
        $settings = [
            'company_name' => 'Công ty Cổ phần Giải pháp Công nghệ HRM',
            'tax_code' => '0101234567',
            'website' => 'www.HRM.vn',
            'address' => 'Tòa nhà Innovation, Công viên Phần mềm Quang Trung, Quận 12, TP.HCM',
            'leave_days_per_month' => '1',
            'leave_days_per_year' => '12',
            // Giờ làm việc & chính sách chấm công
            'work_start_time' => '08:00',
            'work_end_time' => '17:30',
            'checkin_open_time' => '07:00',
            'late_grace_minutes' => '5',
            'late_level1_minutes' => '15',
            'late_level2_minutes' => '30',
            'checkin_deadline' => '10:00',
            'checkout_deadline' => '22:00',
            // Đã chốt công tới hôm qua (dữ liệu seed đã có sẵn trạng thái đầy đủ).
            'attendance_closed_through' => Carbon::yesterday()->toDateString(),
        ];

        foreach ($settings as $key => $value) {
            CompanySetting::put($key, $value);
        }
    }

    private function seedDepartments(): array
    {
        $data = [
            ['name' => 'Bộ phận Phát triển', 'code' => 'DEV-01', 'head_name' => 'Trần Thế Vinh', 'color' => 'blue'],
            ['name' => 'Phòng Nhân sự', 'code' => 'HRM-02', 'head_name' => 'Lê Minh Anh', 'color' => 'orange'],
            ['name' => 'Marketing & Sales', 'code' => 'SAL-05', 'head_name' => 'Nguyễn Thu Thủy', 'color' => 'green'],
            ['name' => 'Phòng Tài chính - Kế toán', 'code' => 'FIN-03', 'head_name' => 'Phạm Quốc Bảo', 'color' => 'purple'],
            ['name' => 'Phòng Thiết kế', 'code' => 'DES-04', 'head_name' => 'Đỗ Lê Phương', 'color' => 'pink'],
        ];

        $departments = [];
        foreach ($data as $item) {
            $departments[$item['code']] = Department::create($item);
        }

        return $departments;
    }

    private function seedEmployees(array $departments): array
    {
        $admin = Employee::create([
            'code' => 'PP0001',
            'name' => 'Lê Minh Anh',
            'email' => 'admin@HRM.vn',
            'personal_email' => 'minhanh.le@gmail.com',
            'phone' => '0901234567',
            'gender' => 'female',
            'dob' => '1990-04-12',
            'national_id' => '079090001234',
            'marital_status' => 'Đã kết hôn',
            'nationality' => 'Việt Nam',
            'permanent_address' => '128 Nguyễn Trãi, Quận 1, TP.HCM',
            'temporary_address' => '128 Nguyễn Trãi, Quận 1, TP.HCM',
            'department_id' => $departments['HRM-02']->id,
            'position' => 'Trưởng phòng Nhân sự',
            'level' => 'Manager',
            'contract_type' => 'Toàn thời gian',
            'join_date' => '2020-01-06',
            'status' => 'active',
            'bank_name' => 'Vietcombank',
            'bank_account' => '0071000123456',
            'bank_holder' => 'LE MINH ANH',
            'base_salary' => 35000000,
            'lunch_allowance' => 730000,
            'emergency_contact' => 'Lê Văn Nam - 0912345678',
            'skills' => ['Tuyển dụng', 'C&B', 'Đào tạo'],
        ]);

        $rows = [
            ['Trần Thế Vinh', 'vinh.tran@HRM.vn', 'male', 'DEV-01', 'Trưởng nhóm Kỹ thuật', 'Lead', 45000000, ['Laravel', 'Vue.js', 'AWS']],
            ['Nguyễn Hoàng An', 'an.nguyen@HRM.vn', 'male', 'DEV-01', 'Lập trình viên Frontend', 'Senior', 28000000, ['ReactJS', 'Tailwind', 'TypeScript']],
            ['Phạm Đức Hùng', 'hung.pham@HRM.vn', 'male', 'DEV-01', 'Lập trình viên Backend', 'Senior', 30000000, ['PHP', 'MySQL', 'Docker']],
            ['Đỗ Lê Phương', 'phuong.do@HRM.vn', 'female', 'DES-04', 'Trưởng nhóm Thiết kế', 'Lead', 32000000, ['Figma', 'UI/UX', 'Illustrator']],
            ['Vũ Thị Lan', 'lan.vu@HRM.vn', 'female', 'DES-04', 'Chuyên viên Thiết kế', 'Middle', 20000000, ['Figma', 'Photoshop']],
            ['Nguyễn Thu Thủy', 'thuy.nguyen@HRM.vn', 'female', 'SAL-05', 'Trưởng phòng Marketing', 'Manager', 38000000, ['SEO', 'Google Ads', 'Content']],
            ['Bùi Minh Khôi', 'khoi.bui@HRM.vn', 'male', 'SAL-05', 'Chuyên viên Sales', 'Middle', 18000000, ['B2B Sales', 'CRM']],
            ['Phạm Quốc Bảo', 'bao.pham@HRM.vn', 'male', 'FIN-03', 'Kế toán trưởng', 'Manager', 36000000, ['Kế toán', 'Thuế', 'Misa']],
            ['Trần Thị Hồng', 'hong.tran@HRM.vn', 'female', 'FIN-03', 'Nhân viên Kế toán', 'Junior', 15000000, ['Excel', 'Kế toán']],
            ['Hoàng Văn Tài', 'tai.hoang@HRM.vn', 'male', 'DEV-01', 'Kỹ sư QA', 'Middle', 22000000, ['Testing', 'Automation']],
            ['Đặng Thùy Dung', 'dung.dang@HRM.vn', 'female', 'HRM-02', 'Chuyên viên Tuyển dụng', 'Middle', 17000000, ['Tuyển dụng', 'Sourcing']],
            ['Ngô Gia Bảo', 'bao.ngo@HRM.vn', 'male', 'DEV-01', 'Thực tập sinh Lập trình', 'Intern', 6000000, ['JavaScript', 'HTML/CSS']],
        ];

        $employees = ['PP0001' => $admin];
        $vinh = null;
        foreach ($rows as $i => $row) {
            [$name, $email, $gender, $deptCode, $position, $level, $salary, $skills] = $row;
            $code = 'PP' . str_pad((string) ($i + 2), 4, '0', STR_PAD_LEFT);
            $emp = Employee::create([
                'code' => $code,
                'name' => $name,
                'email' => $email,
                'personal_email' => null,
                'phone' => '09' . str_pad((string) rand(10000000, 99999999), 8, '0'),
                'gender' => $gender,
                'dob' => Carbon::create(rand(1988, 2001), rand(1, 12), rand(1, 28))->toDateString(),
                'national_id' => '079' . rand(100000000, 999999999),
                'marital_status' => rand(0, 1) ? 'Độc thân' : 'Đã kết hôn',
                'nationality' => 'Việt Nam',
                'permanent_address' => rand(1, 200) . ' Đường ' . rand(1, 50) . ', Quận ' . rand(1, 12) . ', TP.HCM',
                'department_id' => $departments[$deptCode]->id,
                'position' => $position,
                'level' => $level,
                'contract_type' => $level === 'Intern' ? 'Thực tập' : 'Toàn thời gian',
                'join_date' => Carbon::now()->subMonths(rand(1, 48))->toDateString(),
                'manager_id' => null,
                'status' => 'active',
                'bank_name' => 'Vietcombank',
                'bank_account' => (string) rand(1000000000, 9999999999),
                'bank_holder' => \Illuminate\Support\Str::upper($this->stripAccents($name)),
                'base_salary' => $salary,
                'lunch_allowance' => 730000,
                'emergency_contact' => 'Người thân - 09' . rand(10000000, 99999999),
                'skills' => $skills,
            ]);
            $employees[$code] = $emp;
            if ($name === 'Trần Thế Vinh') {
                $vinh = $emp;
            }
        }

        // Assign a few managers
        if ($vinh) {
            Employee::where('department_id', $departments['DEV-01']->id)
                ->where('id', '!=', $vinh->id)
                ->update(['manager_id' => $vinh->id]);
        }

        // Mark two employees on leave for dashboard realism
        Employee::where('code', 'PP0006')->update(['status' => 'on_leave']);
        Employee::where('code', 'PP0009')->update(['status' => 'on_leave']);

        return [$admin, collect($employees)->values()];
    }

    private function seedUsers(Employee $admin, $employees): void
    {
        // Mỗi nhân viên gắn với một tài khoản đăng nhập (liên kết qua user_id).
        foreach ($employees as $emp) {
            // F11: role không còn nằm trong fillable ⇒ gán qua property.
            $user = new User([
                'name' => $emp->name,
                'email' => $emp->email,
                'password' => Hash::make('password'),
            ]);
            $user->role = $emp->email === $admin->email ? User::ROLE_SUPER_ADMIN : User::ROLE_USER;
            $user->email_verified_at = now();
            $user->save();

            $emp->update(['user_id' => $user->id]);
        }
    }

    private function linkDepartmentHeads(array $departments): void
    {
        foreach ($departments as $dept) {
            if (! $dept->head_name) {
                continue;
            }

            $head = Employee::where('name', $dept->head_name)->first();
            if ($head) {
                $dept->update(['head_employee_id' => $head->id]);
            }
        }
    }

    private function seedAttendances($employees): void
    {
        foreach ($employees as $emp) {
            if ($emp->status === 'resigned') {
                continue;
            }
            for ($d = 29; $d >= 0; $d--) {
                $date = Carbon::today()->subDays($d);
                if ($date->isWeekend()) {
                    continue;
                }

                $roll = rand(1, 100);
                if ($emp->status === 'on_leave' && $d < 3) {
                    Attendance::create([
                        'employee_id' => $emp->id,
                        'work_date' => $date->toDateString(),
                        'status' => 'leave',
                        'total_minutes' => 0,
                    ]);
                    continue;
                }

                if ($roll <= 5) {
                    Attendance::create([
                        'employee_id' => $emp->id,
                        'work_date' => $date->toDateString(),
                        'status' => 'absent',
                        'total_minutes' => 0,
                    ]);
                    continue;
                }

                $late = $roll <= 20;
                if ($late) {
                    // Đi muộn 6–50 phút so với 08:00 để phủ đủ 3 mức cảnh báo.
                    $lateMinutes = rand(6, 50);
                    $checkIn = $date->copy()->setTime(8, 0)->addMinutes($lateMinutes);
                } else {
                    $lateMinutes = 0;
                    $checkIn = $date->copy()->setTime(7, rand(30, 59));
                }
                $checkOut = $date->copy()->setTime(17, rand(30, 59));
                $isToday = $date->isToday();

                Attendance::create([
                    'employee_id' => $emp->id,
                    'work_date' => $date->toDateString(),
                    'check_in' => $checkIn->format('H:i:s'),
                    'check_out' => $isToday ? null : $checkOut->format('H:i:s'),
                    'total_minutes' => $isToday ? 0 : (int) round($checkIn->diffInMinutes($checkOut, true)),
                    'late_minutes' => $lateMinutes,
                    'status' => $isToday ? ($late ? 'late' : 'working') : ($late ? 'late' : 'on_time'),
                ]);
            }
        }
    }

    private function seedLeaves($employees, Employee $admin): void
    {
        $types = ['monthly', 'annual', 'sick', 'unpaid', 'maternity', 'remote'];
        $reasons = [
            'Về quê thăm gia đình',
            'Khám sức khỏe định kỳ',
            'Giải quyết việc cá nhân',
            'Nghỉ ốm',
            'Làm việc từ xa do bận việc gia đình',
            'Du lịch cùng gia đình',
        ];

        foreach ($employees as $i => $emp) {
            if ($emp->email === $admin->email) {
                continue;
            }
            $count = rand(1, 3);
            for ($j = 0; $j < $count; $j++) {
                $start = Carbon::today()->addDays(rand(-40, 20));
                $length = rand(1, 4);
                $end = $start->copy()->addDays($length - 1);
                $status = ['pending', 'approved', 'approved', 'rejected'][rand(0, 3)];

                LeaveRequest::create([
                    'employee_id' => $emp->id,
                    'type' => $types[array_rand($types)],
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                    'days' => $length,
                    'reason' => $reasons[array_rand($reasons)],
                    'status' => $status,
                    'approver_name' => $status === 'pending' ? null : $admin->name,
                ]);
            }
        }
    }

    private function seedKpis(array $departments, $employees): void
    {
        $byCode = $employees->keyBy('code');

        $kpis = [
            [
                'name' => 'Hoàn thành Website dự án HRM',
                'description' => 'Xây dựng và triển khai nền tảng website mới tích hợp hệ thống quản lý nhân sự HRM, đảm bảo trải nghiệm người dùng tối ưu và hiệu năng cao.',
                'dept' => 'DEV-01', 'owner' => 'PP0002', 'measure_type' => 'percent', 'unit' => '%',
                'target_value' => 100, 'progress' => 70, 'priority' => 'high', 'status' => 'in_progress',
                'deadline' => Carbon::create(2026, 12, 30)->toDateString(),
                'phases' => [
                    ['Thiết kế UI/UX', 'PP0005', -60, 'done'],
                    ['Phát triển Frontend', 'PP0003', -20, 'in_progress'],
                    ['Phát triển Backend', 'PP0004', 10, 'pending'],
                    ['Testing & QA', 'PP0011', 30, 'pending'],
                ],
            ],
            [
                'name' => 'Tăng trưởng doanh thu Quý 2',
                'description' => 'Đẩy mạnh hoạt động bán hàng và marketing để đạt mục tiêu doanh thu 5 tỷ đồng trong quý.',
                'dept' => 'SAL-05', 'owner' => 'PP0007', 'measure_type' => 'count', 'unit' => 'Tỷ VNĐ',
                'target_value' => 5, 'progress' => 55, 'priority' => 'high', 'status' => 'on_track',
                'deadline' => Carbon::today()->addDays(45)->toDateString(),
                'phases' => [
                    ['Chiến dịch quảng cáo', 'PP0007', -10, 'done'],
                    ['Chăm sóc khách hàng lớn', 'PP0008', 20, 'in_progress'],
                ],
            ],
            [
                'name' => 'Tuyển dụng 15 nhân sự mới',
                'description' => 'Mở rộng đội ngũ cho các phòng ban trọng điểm trong năm nay.',
                'dept' => 'HRM-02', 'owner' => 'PP0001', 'measure_type' => 'count', 'unit' => 'Người',
                'target_value' => 15, 'progress' => 40, 'priority' => 'medium', 'status' => 'behind',
                'deadline' => Carbon::today()->addDays(90)->toDateString(),
                'phases' => [
                    ['Đăng tin & Sourcing', 'PP0012', -5, 'done'],
                    ['Phỏng vấn vòng 1', 'PP0012', 15, 'in_progress'],
                    ['Onboarding', 'PP0001', 40, 'pending'],
                ],
            ],
            [
                'name' => 'Chuẩn hóa quy trình kế toán',
                'description' => 'Số hóa và chuẩn hóa toàn bộ quy trình kế toán nội bộ để giảm thời gian xử lý.',
                'dept' => 'FIN-03', 'owner' => 'PP0009', 'measure_type' => 'milestone', 'unit' => 'Cột mốc',
                'target_value' => 4, 'progress' => 100, 'priority' => 'low', 'status' => 'done',
                'deadline' => Carbon::today()->subDays(10)->toDateString(),
                'phases' => [
                    ['Rà soát quy trình cũ', 'PP0009', -40, 'done'],
                    ['Triển khai phần mềm', 'PP0010', -15, 'done'],
                ],
            ],
            [
                'name' => 'Nâng cấp bộ nhận diện thương hiệu',
                'description' => 'Làm mới logo, bộ màu và tài liệu thương hiệu cho toàn công ty.',
                'dept' => 'DES-04', 'owner' => 'PP0005', 'measure_type' => 'percent', 'unit' => '%',
                'target_value' => 100, 'progress' => 25, 'priority' => 'medium', 'status' => 'in_progress',
                'deadline' => Carbon::today()->addDays(60)->toDateString(),
                'phases' => [
                    ['Nghiên cứu & Moodboard', 'PP0006', 5, 'in_progress'],
                    ['Thiết kế logo mới', 'PP0005', 30, 'pending'],
                ],
            ],
        ];

        foreach ($kpis as $item) {
            $kpi = Kpi::create([
                'name' => $item['name'],
                'description' => $item['description'],
                'department_id' => $departments[$item['dept']]->id ?? null,
                'owner_employee_id' => $byCode[$item['owner']]->id ?? null,
                'measure_type' => $item['measure_type'],
                'unit' => $item['unit'],
                'target_value' => $item['target_value'],
                'current_value' => round($item['target_value'] * $item['progress'] / 100, 2),
                'progress' => $item['progress'],
                'priority' => $item['priority'],
                'status' => $item['status'],
                'deadline' => $item['deadline'],
            ]);

            foreach ($item['phases'] as $phase) {
                [$phaseName, $assigneeCode, $dayOffset, $phaseStatus] = $phase;
                $kpi->phases()->create([
                    'name' => $phaseName,
                    'assignee_employee_id' => $byCode[$assigneeCode]->id ?? null,
                    'deadline' => Carbon::today()->addDays($dayOffset)->toDateString(),
                    'status' => $phaseStatus,
                ]);
            }
        }
    }

    private function stripAccents(string $str): string
    {
        $map = [
            'à','á','ả','ã','ạ','ă','ắ','ằ','ẳ','ẵ','ặ','â','ấ','ầ','ẩ','ẫ','ậ',
            'è','é','ẻ','ẽ','ẹ','ê','ế','ề','ể','ễ','ệ',
            'ì','í','ỉ','ĩ','ị',
            'ò','ó','ỏ','õ','ọ','ô','ố','ồ','ổ','ỗ','ộ','ơ','ớ','ờ','ở','ỡ','ợ',
            'ù','ú','ủ','ũ','ụ','ư','ứ','ừ','ử','ữ','ự',
            'ỳ','ý','ỷ','ỹ','ỵ','đ',
        ];
        $repl = [
            'a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a',
            'e','e','e','e','e','e','e','e','e','e','e',
            'i','i','i','i','i',
            'o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o',
            'u','u','u','u','u','u','u','u','u','u','u',
            'y','y','y','y','y','d',
        ];

        return str_replace($map, $repl, mb_strtolower($str));
    }
}
