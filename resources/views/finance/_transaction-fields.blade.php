@php $transaction = $transaction ?? null; @endphp
<div class="grid grid-cols-1 md:grid-cols-2 gap-md">
    <div>
        <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Quỹ *</label>
        <select name="account_id" required class="w-full h-11 px-md border border-outline-variant rounded-lg">
            @foreach ($accounts as $acc)
                <option value="{{ $acc->id }}" @selected(old('account_id', $transaction?->account_id) == $acc->id)>{{ $acc->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Chiều *</label>
        <select name="direction" required class="w-full h-11 px-md border border-outline-variant rounded-lg">
            <option value="income" @selected(old('direction', $transaction?->direction) === 'income')>Thu</option>
            <option value="expense" @selected(old('direction', $transaction?->direction ?? 'expense') === 'expense')>Chi</option>
        </select>
    </div>
    <div>
        <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Số tiền (₫) *</label>
        <input name="amount" type="text" inputmode="numeric" required value="{{ old('amount', $transaction ? (int) $transaction->amount : '') }}" class="money-input w-full h-11 px-md border border-outline-variant rounded-lg">
    </div>
    <div>
        <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Ngày *</label>
        <input name="occurred_on" type="date" required value="{{ old('occurred_on', optional($transaction?->occurred_on)->toDateString() ?? now()->toDateString()) }}" class="w-full h-11 px-md border border-outline-variant rounded-lg">
    </div>
    <div>
        <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Danh mục</label>
        <select name="category_id" class="w-full h-11 px-md border border-outline-variant rounded-lg">
            <option value="">— Không —</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" @selected(old('category_id', $transaction?->category_id) == $cat->id)>{{ $cat->name }} ({{ $cat->direction_label }})</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Chứng từ / Tham chiếu</label>
        <input name="reference" type="text" value="{{ old('reference', $transaction?->reference) }}" class="w-full h-11 px-md border border-outline-variant rounded-lg">
    </div>
</div>
<div class="mt-md">
    <label class="flex items-center gap-xs font-label-md text-label-md text-on-surface-variant">
        <input type="checkbox" name="is_contribution" value="1" @checked(old('is_contribution', $transaction?->is_contribution)) class="rounded border-outline-variant text-primary focus:ring-primary/20">
        <span>Là khoản nạp vốn vào công ty (chỉ áp dụng cho giao dịch Thu)</span>
    </label>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-md mt-md">
    <div>
        <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Người nạp (nếu nạp vốn)</label>
        <input name="contributor_name" type="text" value="{{ old('contributor_name', $transaction?->contributor_name) }}" class="w-full h-11 px-md border border-outline-variant rounded-lg">
    </div>
    <div>
        <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Diễn giải</label>
        <input name="description" type="text" value="{{ old('description', $transaction?->description) }}" class="w-full h-11 px-md border border-outline-variant rounded-lg">
    </div>
</div>
