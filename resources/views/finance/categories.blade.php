@extends('layouts.app')

@section('title', 'Danh mục thu/chi')
@section('page-title', 'Danh mục thu/chi')

@section('content')
<div class="px-md md:px-xl pt-lg pb-32">
    <div class="max-w-container-max mx-auto">
        <x-page-header title="Danh mục thu/chi" subtitle="Phân loại các khoản thu và chi" />

        @include('finance._nav')
        @include('finance._flash')

        <!-- Thêm danh mục -->
        <form method="POST" action="{{ route('finance.categories.store') }}" class="glass-card p-lg rounded-xl shadow-sm mb-lg grid grid-cols-1 md:grid-cols-[1fr_160px_140px_auto] gap-md items-end">
            @csrf
            <div>
                <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Tên danh mục *</label>
                <input name="name" type="text" required placeholder="VD: Lương, Thuê văn phòng" class="w-full h-11 px-md border border-outline-variant rounded-lg">
            </div>
            <div>
                <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Chiều *</label>
                <select name="direction" class="w-full h-11 px-md border border-outline-variant rounded-lg">
                    <option value="expense">Chi</option>
                    <option value="income">Thu</option>
                </select>
            </div>
            <div>
                <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Màu</label>
                <input name="color" type="color" value="#6750A4" class="w-full h-11 px-1 border border-outline-variant rounded-lg">
            </div>
            <button type="submit" class="h-11 px-lg bg-primary text-on-primary rounded-lg font-medium text-label-md">Thêm</button>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
            @forelse ($categories as $category)
                <div class="glass-card p-md rounded-xl shadow-sm flex items-center justify-between">
                    <div class="flex items-center gap-md">
                        <span class="w-9 h-9 rounded-lg flex items-center justify-center text-white" style="background-color: {{ $category->color ?: '#6750A4' }}">
                            <span class="material-symbols-outlined text-[18px]">{{ $category->direction === 'income' ? 'south_west' : 'north_east' }}</span>
                        </span>
                        <div>
                            <p class="font-body-md text-body-md font-medium text-on-surface">{{ $category->name }}</p>
                            <p class="text-[12px] text-on-surface-variant">{{ $category->direction_label }} • {{ $category->transactions_count }} giao dịch</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-xs">
                        <button type="button" onclick="openModal('edit-cat-{{ $category->id }}')" class="w-8 h-8 rounded-lg hover:bg-surface-container text-on-surface-variant material-symbols-outlined text-[18px]">edit</button>
                        <form method="POST" action="{{ route('finance.categories.destroy', $category) }}" onsubmit="return confirm('Xoá danh mục này?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="w-8 h-8 rounded-lg text-error hover:bg-error-container material-symbols-outlined text-[18px]">delete</button>
                        </form>
                    </div>
                </div>

                <x-finance-modal id="edit-cat-{{ $category->id }}" title="Sửa danh mục">
                    <form method="POST" action="{{ route('finance.categories.update', $category) }}" class="space-y-md">
                        @csrf @method('PUT')
                        <div>
                            <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Tên *</label>
                            <input name="name" type="text" required value="{{ $category->name }}" class="w-full h-11 px-md border border-outline-variant rounded-lg">
                        </div>
                        <div>
                            <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Chiều *</label>
                            <select name="direction" class="w-full h-11 px-md border border-outline-variant rounded-lg">
                                <option value="expense" @selected($category->direction === 'expense')>Chi</option>
                                <option value="income" @selected($category->direction === 'income')>Thu</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Màu</label>
                            <input name="color" type="color" value="{{ $category->color ?: '#6750A4' }}" class="w-full h-11 px-1 border border-outline-variant rounded-lg">
                        </div>
                        <div class="flex justify-end gap-sm pt-sm">
                            <button type="button" onclick="closeModal('edit-cat-{{ $category->id }}')" class="px-md py-sm rounded-lg border border-outline-variant text-label-md">Huỷ</button>
                            <button type="submit" class="px-lg py-sm bg-primary text-on-primary rounded-lg font-medium text-label-md">Lưu</button>
                        </div>
                    </form>
                </x-finance-modal>
            @empty
                <div class="md:col-span-2 glass-card p-xl rounded-xl text-center text-on-surface-variant">Chưa có danh mục nào.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openModal(id) { document.getElementById('modal-' + id)?.classList.remove('hidden'); }
    function closeModal(id) { document.getElementById('modal-' + id)?.classList.add('hidden'); }
</script>
@endpush
