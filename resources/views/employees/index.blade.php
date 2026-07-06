@extends('layouts.app')

@section('title', 'Nhân viên')
@section('page-title', 'Danh sách nhân viên')

@section('content')
<div class="px-md md:px-xl pt-lg">
    <div class="max-w-container-max mx-auto space-y-lg">
        <!-- Toolbar -->
        <form method="GET" action="{{ route('employees.index') }}"
              class="bg-surface-container-lowest p-md rounded-2xl shadow-sm flex flex-col md:flex-row gap-md items-center justify-between border border-outline-variant/30">
            <div class="flex flex-wrap items-center gap-sm w-full md:w-auto">
                <div class="relative flex-1 md:flex-none">
                    <span class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
                    <input name="q" value="{{ request('q') }}" type="text" placeholder="Tìm kiếm nhân viên..."
                           class="pl-xl pr-md py-sm bg-surface rounded-xl border border-outline-variant w-full md:w-[320px] focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-body-md">
                </div>
                <select name="department" onchange="this.form.submit()" class="bg-surface border border-outline-variant rounded-xl py-sm pl-md pr-xl text-body-md focus:ring-2 focus:ring-primary/20 cursor-pointer">
                    <option value="">Tất cả phòng ban</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->id }}" @selected(request('department') == $dept->id)>{{ $dept->name }}</option>
                    @endforeach
                </select>
                <select name="status" onchange="this.form.submit()" class="bg-surface border border-outline-variant rounded-xl py-sm pl-md pr-xl text-body-md focus:ring-2 focus:ring-primary/20 cursor-pointer">
                    <option value="">Trạng thái</option>
                    @foreach (\App\Models\Employee::STATUS_LABELS as $key => $label)
                        <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @can('admin')
            <a href="{{ route('employees.create') }}" class="flex items-center gap-xs bg-primary text-on-primary px-lg py-sm rounded-xl font-semibold shadow-md shadow-primary/20 hover:bg-on-primary-fixed-variant active:scale-95 transition-all w-full md:w-auto justify-center">
                <span class="material-symbols-outlined text-[20px]">person_add</span>
                <span class="text-body-md">Thêm nhân viên</span>
            </a>
            @endcan
        </form>

        <!-- Table -->
        <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/30 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-high/50 border-b border-outline-variant">
                            <th class="px-lg py-md font-semibold text-label-md uppercase tracking-wider text-on-surface-variant">Nhân viên</th>
                            <th class="px-md py-md font-semibold text-label-md uppercase tracking-wider text-on-surface-variant">Mã NV</th>
                            <th class="px-md py-md font-semibold text-label-md uppercase tracking-wider text-on-surface-variant">Email</th>
                            <th class="px-md py-md font-semibold text-label-md uppercase tracking-wider text-on-surface-variant">Phòng ban</th>
                            <th class="px-md py-md font-semibold text-label-md uppercase tracking-wider text-on-surface-variant">Chức vụ</th>
                            <th class="px-md py-md font-semibold text-label-md uppercase tracking-wider text-on-surface-variant">Trạng thái</th>
                            <th class="px-lg py-md"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/30">
                        @forelse ($employees as $employee)
                            <tr class="hover:bg-surface-container transition-colors group">
                                <td class="px-lg py-md">
                                    <a href="{{ route('employees.show', $employee) }}" class="flex items-center gap-md">
                                        <x-avatar :name="$employee->name" class="w-10 h-10 border border-outline-variant" />
                                        <span class="font-body-md font-semibold text-on-surface">{{ $employee->name }}</span>
                                    </a>
                                </td>
                                <td class="px-md py-md text-body-md text-on-surface-variant">{{ $employee->code }}</td>
                                <td class="px-md py-md text-body-md text-on-surface-variant">{{ $employee->email }}</td>
                                <td class="px-md py-md text-body-md text-on-surface-variant">{{ $employee->department?->name ?? '—' }}</td>
                                <td class="px-md py-md text-body-md text-on-surface-variant">{{ $employee->position ?? '—' }}</td>
                                <td class="px-md py-md">
                                    <x-status-badge :status="$employee->status" :label="mb_strtoupper($employee->status_label)" />
                                </td>
                                <td class="px-lg py-md text-right">
                                    @can('admin')
                                    <div class="flex items-center justify-end gap-xs">
                                        <a href="{{ route('employees.edit', $employee) }}" class="material-symbols-outlined text-outline hover:text-primary transition-colors">edit</a>
                                        <form method="POST" action="{{ route('employees.destroy', $employee) }}" onsubmit="return confirm('Xóa nhân viên {{ $employee->name }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="material-symbols-outlined text-outline hover:text-error transition-colors">delete</button>
                                        </form>
                                    </div>
                                    @else
                                        <a href="{{ route('employees.show', $employee) }}" class="material-symbols-outlined text-outline hover:text-primary transition-colors">visibility</a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-lg py-xl text-center text-on-surface-variant">Không tìm thấy nhân viên nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-lg py-md flex items-center justify-between border-t border-outline-variant bg-surface-container-low/50">
                <div class="text-body-md text-on-surface-variant">
                    Hiển thị <span class="font-bold">{{ $employees->firstItem() ?? 0 }}-{{ $employees->lastItem() ?? 0 }}</span> trong <span class="font-bold">{{ $employees->total() }}</span> nhân viên
                </div>
                <div>{{ $employees->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
