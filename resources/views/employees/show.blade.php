@extends('layouts.app')

@section('title', $employee->name)
@section('page-title', 'Hồ sơ nhân viên')

@section('content')
<div class="px-md md:px-xl pt-lg pb-xl">
    <div class="max-w-container-max mx-auto">
        <div class="flex items-center justify-between mb-lg">
            <a href="{{ route('employees.index') }}" class="flex items-center gap-sm text-on-surface-variant hover:text-primary transition-colors">
                <span class="material-symbols-outlined">arrow_back</span>
                <span class="font-body-md">Quay lại danh sách</span>
            </a>
            @can('admin')
            <div class="flex items-center gap-md">
                <a href="{{ route('employees.edit', $employee) }}" class="bg-primary text-on-primary px-lg py-sm rounded-full font-label-md font-semibold shadow-md active:scale-95 transition-all flex items-center gap-xs">
                    <span class="material-symbols-outlined text-[18px]">edit</span>
                    Chỉnh sửa hồ sơ
                </a>
            </div>
            @endcan
        </div>

        <!-- Profile Header -->
        <div class="bg-surface-container-lowest rounded-xl p-xl shadow-sm border border-outline-variant/30 mb-lg flex flex-col md:flex-row gap-xl items-start md:items-center">
            <x-avatar :name="$employee->name" class="w-32 h-32 !rounded-2xl shadow-lg border-4 border-surface text-[40px]" />
            <div class="flex-1">
                <div class="flex items-center gap-sm mb-xs">
                    <h2 class="font-headline-lg text-on-surface">{{ $employee->name }}</h2>
                    <x-status-badge :status="$employee->status" :label="$employee->status_label" class="uppercase tracking-widest" />
                </div>
                <p class="font-body-md text-on-surface-variant mb-md flex items-center gap-xs">
                    <span class="material-symbols-outlined text-[18px]">work</span>
                    {{ $employee->position ?? 'Chưa cập nhật chức vụ' }}
                </p>
                <div class="flex flex-wrap gap-lg">
                    <div class="flex items-center gap-xs text-on-surface-variant">
                        <span class="material-symbols-outlined text-[18px]">mail</span>
                        <span class="font-label-md">{{ $employee->email }}</span>
                    </div>
                    <div class="flex items-center gap-xs text-on-surface-variant">
                        <span class="material-symbols-outlined text-[18px]">call</span>
                        <span class="font-label-md">{{ $employee->phone ?? '—' }}</span>
                    </div>
                    <div class="flex items-center gap-xs text-on-surface-variant">
                        <span class="material-symbols-outlined text-[18px]">location_on</span>
                        <span class="font-label-md">{{ $employee->permanent_address ?? '—' }}</span>
                    </div>
                </div>
            </div>
            <div class="hidden lg:grid grid-cols-2 gap-md border-l border-outline-variant/30 pl-xl">
                <div>
                    <p class="text-[10px] text-outline uppercase font-bold tracking-tighter">Mã nhân viên</p>
                    <p class="font-body-md font-semibold">{{ $employee->code }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-outline uppercase font-bold tracking-tighter">Ngày gia nhập</p>
                    <p class="font-body-md font-semibold">{{ $employee->join_date?->format('d/m/Y') ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-outline uppercase font-bold tracking-tighter">Loại hợp đồng</p>
                    <p class="font-body-md font-semibold">{{ $employee->contract_type ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-outline uppercase font-bold tracking-tighter">Cấp bậc</p>
                    <p class="font-body-md font-semibold">{{ $employee->level ?? '—' }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-lg items-start">
            <div class="lg:col-span-2 space-y-lg">
                <div class="bg-surface-container-lowest rounded-xl p-lg shadow-sm border border-outline-variant/30">
                    <div class="flex justify-between items-center mb-lg">
                        <h3 class="font-headline-md text-on-surface">Thông tin cá nhân</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-xl">
                        <div class="space-y-md">
                            <div>
                                <p class="text-[11px] text-outline uppercase font-bold">Họ và tên</p>
                                <p class="font-body-md border-b border-outline-variant/20 pb-xs mt-xs">{{ $employee->name }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] text-outline uppercase font-bold">Ngày sinh</p>
                                <p class="font-body-md border-b border-outline-variant/20 pb-xs mt-xs">{{ $employee->dob?->format('d/m/Y') ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] text-outline uppercase font-bold">Giới tính</p>
                                <p class="font-body-md border-b border-outline-variant/20 pb-xs mt-xs">{{ $employee->gender_label ?? '—' }}</p>
                            </div>
                        </div>
                        <div class="space-y-md">
                            <div>
                                <p class="text-[11px] text-outline uppercase font-bold">Số CCCD</p>
                                <p class="font-body-md border-b border-outline-variant/20 pb-xs mt-xs">{{ $employee->national_id ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] text-outline uppercase font-bold">Tình trạng hôn nhân</p>
                                <p class="font-body-md border-b border-outline-variant/20 pb-xs mt-xs">{{ $employee->marital_status ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] text-outline uppercase font-bold">Quốc tịch</p>
                                <p class="font-body-md border-b border-outline-variant/20 pb-xs mt-xs">{{ $employee->nationality ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-surface-container-lowest rounded-xl p-lg shadow-sm border border-outline-variant/30">
                    <div class="flex justify-between items-center mb-lg">
                        <h3 class="font-headline-md text-on-surface">Địa chỉ &amp; Liên lạc</h3>
                    </div>
                    <div class="space-y-md">
                        <div>
                            <p class="text-[11px] text-outline uppercase font-bold">Địa chỉ thường trú</p>
                            <p class="font-body-md border-b border-outline-variant/20 pb-xs mt-xs">{{ $employee->permanent_address ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] text-outline uppercase font-bold">Địa chỉ tạm trú</p>
                            <p class="font-body-md border-b border-outline-variant/20 pb-xs mt-xs">{{ $employee->temporary_address ?? '—' }}</p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                            <div>
                                <p class="text-[11px] text-outline uppercase font-bold">Email cá nhân</p>
                                <p class="font-body-md border-b border-outline-variant/20 pb-xs mt-xs">{{ $employee->personal_email ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] text-outline uppercase font-bold">Người liên hệ khẩn cấp</p>
                                <p class="font-body-md border-b border-outline-variant/20 pb-xs mt-xs">{{ $employee->emergency_contact ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-surface-container-lowest rounded-xl p-lg shadow-sm border border-outline-variant/30">
                    <h3 class="font-headline-md text-on-surface mb-lg">Thông tin ngân hàng &amp; Lương</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-md">
                        <div>
                            <p class="text-[11px] text-outline uppercase font-bold">Ngân hàng</p>
                            <p class="font-body-md mt-xs">{{ $employee->bank_name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] text-outline uppercase font-bold">Số tài khoản</p>
                            <p class="font-body-md mt-xs">{{ $employee->bank_account ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] text-outline uppercase font-bold">Lương cơ bản</p>
                            <p class="font-body-md mt-xs font-semibold">{{ $employee->base_salary ? number_format($employee->base_salary, 0, ',', '.') . ' VND' : '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-lg">
                <div class="bg-surface-container-lowest rounded-xl p-lg shadow-sm border border-outline-variant/30">
                    <h3 class="font-label-md text-outline uppercase font-bold mb-md">Trạng thái làm việc</h3>
                    <div class="space-y-md">
                        <div class="flex items-center justify-between">
                            <span class="font-body-md text-on-surface-variant">Phòng ban</span>
                            <span class="font-body-md font-semibold text-primary">{{ $employee->department?->name ?? '—' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-body-md text-on-surface-variant">Người quản lý</span>
                            <div class="flex items-center gap-xs">
                                @if ($employee->manager)
                                    <x-avatar :name="$employee->manager->name" class="w-6 h-6 text-[10px]" />
                                    <span class="font-body-md font-semibold">{{ $employee->manager->name }}</span>
                                @else
                                    <span class="font-body-md">—</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-body-md text-on-surface-variant">Thâm niên</span>
                            <span class="font-body-md font-semibold">{{ $employee->join_date ? $employee->join_date->diffForHumans(null, true) : '—' }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-surface-container-lowest rounded-xl p-lg shadow-sm border border-outline-variant/30">
                    <h3 class="font-label-md text-outline uppercase font-bold mb-md">Kỹ năng &amp; Chuyên môn</h3>
                    <div class="flex flex-wrap gap-xs">
                        @forelse ($employee->skills ?? [] as $skill)
                            <span class="px-sm py-1 bg-surface-variant text-on-surface-variant rounded-full text-[11px] font-semibold">{{ $skill }}</span>
                        @empty
                            <span class="text-body-md text-on-surface-variant">Chưa cập nhật kỹ năng.</span>
                        @endforelse
                    </div>
                </div>

                @if (auth()->id() === $employee->user_id)
                <div id="doi-mat-khau" class="bg-surface-container-lowest rounded-xl p-lg shadow-sm border border-outline-variant/30">
                    <div class="flex items-center gap-xs mb-md">
                        <span class="material-symbols-outlined text-primary text-[20px]">lock</span>
                        <h3 class="font-label-md text-outline uppercase font-bold">Bảo mật &middot; Đổi mật khẩu</h3>
                    </div>

                    @if ($errors->any())
                        <div class="bg-error-container text-on-error-container px-md py-sm rounded-lg mb-md text-body-md">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('account.password') }}" class="space-y-md">
                        @csrf @method('PUT')
                        <div class="space-y-xs">
                            <label class="block text-[11px] text-outline uppercase font-bold">Mật khẩu hiện tại</label>
                            <input name="current_password" type="password" autocomplete="current-password" required
                                   class="w-full h-11 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all">
                        </div>
                        <div class="space-y-xs">
                            <label class="block text-[11px] text-outline uppercase font-bold">Mật khẩu mới</label>
                            <input name="password" type="password" autocomplete="new-password" required
                                   class="w-full h-11 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all">
                            <p class="text-[11px] text-outline">Tối thiểu 6 ký tự.</p>
                        </div>
                        <div class="space-y-xs">
                            <label class="block text-[11px] text-outline uppercase font-bold">Xác nhận mật khẩu mới</label>
                            <input name="password_confirmation" type="password" autocomplete="new-password" required
                                   class="w-full h-11 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all">
                        </div>
                        <button type="submit" class="w-full h-11 bg-primary text-on-primary rounded-lg font-label-md font-semibold shadow-md active:scale-95 transition-all flex items-center justify-center gap-xs">
                            <span class="material-symbols-outlined text-[18px]">save</span>
                            Cập nhật mật khẩu
                        </button>
                    </form>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
