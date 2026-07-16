@php $debt = $debt ?? null; @endphp
<div class="grid grid-cols-1 md:grid-cols-2 gap-md">
    <div>
        <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Loại *</label>
        <select name="type" required class="w-full h-11 px-md border border-outline-variant rounded-lg">
            @foreach (\App\Models\FinanceDebt::TYPE_LABELS as $key => $label)
                <option value="{{ $key }}" @selected(old('type', $debt?->type) === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Đối tác *</label>
        <input name="partner_name" type="text" required value="{{ old('partner_name', $debt?->partner_name) }}" class="w-full h-11 px-md border border-outline-variant rounded-lg">
    </div>
    <div>
        <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Liên hệ đối tác</label>
        <input name="partner_contact" type="text" value="{{ old('partner_contact', $debt?->partner_contact) }}" class="w-full h-11 px-md border border-outline-variant rounded-lg">
    </div>
    <div>
        <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Số tiền (₫) *</label>
        <input name="amount" type="text" inputmode="numeric" required value="{{ old('amount', $debt ? (int) $debt->amount : '') }}" class="money-input w-full h-11 px-md border border-outline-variant rounded-lg">
    </div>
    <div>
        <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Hạn thanh toán</label>
        <input name="due_date" type="date" value="{{ old('due_date', optional($debt?->due_date)->toDateString()) }}" class="w-full h-11 px-md border border-outline-variant rounded-lg">
    </div>
    <div>
        <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Diễn giải</label>
        <input name="description" type="text" value="{{ old('description', $debt?->description) }}" class="w-full h-11 px-md border border-outline-variant rounded-lg">
    </div>
</div>
