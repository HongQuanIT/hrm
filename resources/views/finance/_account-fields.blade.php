@php $account = $account ?? null; @endphp
<div class="grid grid-cols-1 md:grid-cols-2 gap-md">
    <div>
        <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Tên quỹ *</label>
        <input name="name" type="text" required value="{{ old('name', $account?->name) }}" placeholder="VD: Tiền mặt" class="w-full h-11 px-md border border-outline-variant rounded-lg">
    </div>
    <div>
        <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Loại *</label>
        <select name="type" class="w-full h-11 px-md border border-outline-variant rounded-lg">
            @foreach (\App\Models\FinanceAccount::TYPE_LABELS as $key => $label)
                <option value="{{ $key }}" @selected(old('type', $account?->type) === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Ngân hàng</label>
        <input name="bank_name" type="text" value="{{ old('bank_name', $account?->bank_name) }}" class="w-full h-11 px-md border border-outline-variant rounded-lg">
    </div>
    <div>
        <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Số tài khoản</label>
        <input name="account_number" type="text" value="{{ old('account_number', $account?->account_number) }}" class="w-full h-11 px-md border border-outline-variant rounded-lg">
    </div>
    <div>
        <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Số dư đầu kỳ (₫) *</label>
        <input name="opening_balance" type="text" inputmode="numeric" required value="{{ old('opening_balance', $account ? (int) $account->opening_balance : 0) }}" class="money-input w-full h-11 px-md border border-outline-variant rounded-lg">
    </div>
    <div class="flex items-end">
        <label class="flex items-center gap-xs font-label-md text-label-md text-on-surface-variant">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $account?->is_active ?? true)) class="rounded border-outline-variant text-primary focus:ring-primary/20">
            <span>Đang hoạt động</span>
        </label>
    </div>
</div>
<div class="mt-md">
    <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Ghi chú</label>
    <input name="note" type="text" value="{{ old('note', $account?->note) }}" class="w-full h-11 px-md border border-outline-variant rounded-lg">
</div>
