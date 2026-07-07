@extends('layouts.app')

@section('title', 'Tài khoản')
@section('page-title', 'Tài khoản của tôi')

@section('content')
<div class="px-md md:px-xl pt-lg pb-32">
    <div class="max-w-2xl mx-auto">
        @php $user = auth()->user(); @endphp

        <!-- Profile summary -->
        <div class="bg-surface border border-outline-variant rounded-xl shadow-sm p-lg mb-lg flex items-center gap-md">
            <x-avatar :name="$user->name" class="w-14 h-14 !rounded-2xl text-[20px] shadow-sm" />
            <div>
                <p class="font-headline-md text-headline-md text-on-surface">{{ $user->name }}</p>
                <p class="text-body-md text-on-surface-variant">{{ $user->email }} • {{ $user->role_label }}</p>
            </div>
        </div>

        <!-- Change password -->
        <div class="bg-surface border border-outline-variant rounded-xl shadow-sm overflow-hidden">
            <div class="px-lg py-md border-b border-outline-variant flex items-center gap-sm bg-surface-container-low">
                <span class="material-symbols-outlined text-primary">lock</span>
                <h2 class="font-headline-md text-headline-md text-on-surface">Đổi mật khẩu</h2>
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

            <form method="POST" action="{{ route('account.password') }}" class="p-lg space-y-lg">
                @csrf @method('PUT')
                <div class="space-y-xs">
                    <label class="block font-label-md text-label-md text-on-surface-variant">Mật khẩu hiện tại *</label>
                    <input name="current_password" type="password" autocomplete="current-password" required
                           class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
                    <div class="space-y-xs">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Mật khẩu mới *</label>
                        <input name="password" type="password" autocomplete="new-password" required
                               class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all">
                        <p class="text-[11px] text-outline">Tối thiểu 6 ký tự.</p>
                    </div>
                    <div class="space-y-xs">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Xác nhận mật khẩu mới *</label>
                        <input name="password_confirmation" type="password" autocomplete="new-password" required
                               class="w-full h-12 px-md border border-outline-variant rounded-lg font-body-md form-input-ring transition-all">
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="h-12 px-xl bg-primary text-on-primary rounded-lg font-medium shadow-[0_4px_12px_rgba(0,74,198,0.25)] hover:bg-on-primary-fixed-variant active:scale-95 transition-all flex items-center gap-sm">
                        <span class="material-symbols-outlined text-[20px]">save</span>
                        Cập nhật mật khẩu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
