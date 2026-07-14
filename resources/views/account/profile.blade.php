@extends('layouts.app')

@section('title', 'Thông tin cá nhân')
@section('page-title', 'Thông tin cá nhân')

@section('content')
<div class="px-md md:px-xl pt-lg pb-32">
    <div class="max-w-2xl mx-auto">
        @php $user = auth()->user(); @endphp

        <div class="bg-surface border border-outline-variant rounded-xl shadow-sm p-lg mb-lg flex items-center gap-md">
            <x-avatar :name="$user->name" class="w-14 h-14 !rounded-2xl text-[20px] shadow-sm" />
            <div class="flex-1">
                <p class="font-headline-md text-headline-md text-on-surface">{{ $user->name }}</p>
                <p class="text-body-md text-on-surface-variant">{{ $user->email }} • {{ $user->role_label }}</p>
            </div>
            <a href="{{ route('account.edit') }}" class="flex items-center gap-xs px-md py-sm rounded-lg border border-outline-variant text-body-md text-on-surface-variant hover:bg-surface-container transition-colors">
                <span class="material-symbols-outlined text-[20px]">lock</span>
                <span>Đổi mật khẩu</span>
            </a>
        </div>

        @if (session('status'))
            <div class="mb-lg bg-primary/10 text-primary px-lg py-md rounded-xl">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-lg bg-error-container text-on-error-container px-lg py-md rounded-xl">{{ session('error') }}</div>
        @endif

        @if (! $employee)
            <div class="bg-surface border border-outline-variant rounded-xl shadow-sm p-lg text-center text-on-surface-variant">
                Tài khoản của bạn chưa được gắn với hồ sơ nhân viên. Vui lòng liên hệ quản trị viên.
            </div>
        @else
            <div class="bg-surface border border-outline-variant rounded-xl shadow-sm overflow-hidden">
                <div class="px-lg py-md border-b border-outline-variant flex items-center gap-sm bg-surface-container-low">
                    <span class="material-symbols-outlined text-primary">badge</span>
                    <h2 class="font-headline-md text-headline-md text-on-surface">Thông tin liên hệ</h2>
                </div>

                @if ($errors->any())
                    <div class="mx-lg mt-lg bg-error-container text-on-error-container px-lg py-md rounded-xl">
                        <ul class="list-disc list-inside text-body-md">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('account.profile.update') }}" class="p-lg space-y-lg">
                    @csrf @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
                        <div class="space-y-xs">
                            <label class="block font-label-md text-label-md text-on-surface-variant">Số điện thoại</label>
                            <input name="phone" type="text" value="{{ old('phone', $employee->phone) }}"
                                   class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all">
                        </div>
                        <div class="space-y-xs">
                            <label class="block font-label-md text-label-md text-on-surface-variant">Email cá nhân</label>
                            <input name="personal_email" type="email" value="{{ old('personal_email', $employee->personal_email) }}"
                                   class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all">
                        </div>
                    </div>

                    <div class="space-y-xs">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Địa chỉ thường trú</label>
                        <input name="permanent_address" type="text" value="{{ old('permanent_address', $employee->permanent_address) }}"
                               class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all">
                    </div>

                    <div class="space-y-xs">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Địa chỉ tạm trú</label>
                        <input name="temporary_address" type="text" value="{{ old('temporary_address', $employee->temporary_address) }}"
                               class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all">
                    </div>

                    <div class="space-y-xs">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Liên hệ khẩn cấp</label>
                        <input name="emergency_contact" type="text" value="{{ old('emergency_contact', $employee->emergency_contact) }}"
                               class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all">
                    </div>

                    <div class="pt-md border-t border-outline-variant">
                        <p class="text-[12px] text-outline mb-md">Các thông tin như chức danh, phòng ban, lương và vai trò do quản trị viên quản lý.</p>
                        <div class="flex justify-end">
                            <button type="submit" class="h-12 px-xl bg-primary text-on-primary rounded-lg font-medium hover:bg-on-primary-fixed-variant active:scale-95 transition-all flex items-center gap-sm">
                                <span class="material-symbols-outlined text-[20px]">save</span>
                                Lưu thay đổi
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
