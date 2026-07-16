<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayDebtRequest;
use App\Http\Requests\StoreFinanceDebtRequest;
use App\Models\FinanceAccount;
use App\Models\FinanceDebt;
use App\Services\FinanceService;
use Illuminate\Http\Request;

class FinanceDebtController extends Controller
{
    public function index(Request $request)
    {
        $query = FinanceDebt::query()->latest();

        if (in_array($request->input('type'), ['receivable', 'payable'], true)) {
            $query->where('type', $request->input('type'));
        }
        if (in_array($request->input('status'), array_keys(FinanceDebt::STATUS_LABELS), true)) {
            $query->where('status', $request->input('status'));
        }

        $debts = $query->paginate(15)->withQueryString();
        $accounts = FinanceAccount::where('is_active', true)->orderBy('name')->get();

        return view('finance.debts', compact('debts', 'accounts'));
    }

    public function store(StoreFinanceDebtRequest $request)
    {
        FinanceDebt::create($request->validated() + ['status' => 'open']);

        return redirect()->route('finance.debts.index')->with('status', 'Đã thêm công nợ.');
    }

    public function update(StoreFinanceDebtRequest $request, FinanceDebt $debt)
    {
        if (in_array($debt->status, ['paid', 'cancelled'], true)) {
            return back()->with('error', 'Không thể sửa công nợ đã tất toán hoặc đã huỷ.');
        }

        $debt->update($request->validated());

        return redirect()->route('finance.debts.index')->with('status', 'Đã cập nhật công nợ.');
    }

    /**
     * FR-M10-15: ghi nhận một lần thanh toán → sinh giao dịch gắn quỹ + cập nhật trạng thái.
     */
    public function pay(PayDebtRequest $request, FinanceDebt $debt, FinanceService $finance)
    {
        $data = $request->validated();

        if (in_array($debt->status, ['paid', 'cancelled'], true)) {
            return back()->with('error', 'Công nợ này đã tất toán hoặc đã huỷ.');
        }

        // BR-M10-12: không thanh toán vượt số còn lại.
        if ((float) $data['amount'] > $debt->remaining_amount + 0.001) {
            return back()->with('error', 'Số tiền thanh toán vượt quá số còn lại (' . number_format($debt->remaining_amount, 0, ',', '.') . ' ₫).')->withInput();
        }

        // BR-M10-13: payable → chi; receivable → thu.
        $direction = $debt->type === 'payable' ? 'expense' : 'income';

        $debt->transactions()->create([
            'account_id' => $data['account_id'],
            'direction' => $direction,
            'amount' => $data['amount'],
            'is_contribution' => false,
            'occurred_on' => $data['occurred_on'],
            'description' => $data['description'] ?? ('Thanh toán công nợ: ' . $debt->partner_name),
            'created_by' => auth()->id(),
        ]);

        $finance->refreshDebtStatus($debt);

        return redirect()->route('finance.debts.index')->with('status', 'Đã ghi nhận thanh toán công nợ.');
    }

    public function cancel(FinanceDebt $debt)
    {
        if (in_array($debt->status, ['paid', 'cancelled'], true)) {
            return back()->with('error', 'Công nợ này không thể huỷ.');
        }

        $debt->update(['status' => 'cancelled']);

        return back()->with('status', 'Đã huỷ công nợ.');
    }

    public function destroy(FinanceDebt $debt)
    {
        // FR-M10-16: chỉ xoá khi chưa phát sinh thanh toán; ngược lại gợi ý huỷ.
        if ($debt->transactions()->exists()) {
            return back()->with('error', 'Công nợ đã có thanh toán nên không thể xoá. Hãy dùng "Huỷ".');
        }

        $debt->delete();

        return back()->with('status', 'Đã xoá công nợ.');
    }
}
