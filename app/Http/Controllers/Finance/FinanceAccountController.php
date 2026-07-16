<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustBalanceRequest;
use App\Http\Requests\DepositRequest;
use App\Http\Requests\StoreFinanceAccountRequest;
use App\Models\FinanceAccount;
use App\Services\FinanceService;

class FinanceAccountController extends Controller
{
    public function index(FinanceService $finance)
    {
        $accounts = FinanceAccount::orderByDesc('is_active')->orderBy('name')->get();
        $summary = $finance->summary();

        return view('finance.accounts', compact('accounts', 'summary'));
    }

    public function store(StoreFinanceAccountRequest $request)
    {
        FinanceAccount::create($request->validated());

        return redirect()->route('finance.accounts.index')->with('status', 'Đã thêm quỹ tiền.');
    }

    public function update(StoreFinanceAccountRequest $request, FinanceAccount $account)
    {
        $account->update($request->validated());

        return redirect()->route('finance.accounts.index')->with('status', 'Đã cập nhật quỹ tiền.');
    }

    public function destroy(FinanceAccount $account)
    {
        // BR-M10-14: chặn xoá nếu quỹ còn giao dịch (bảo toàn lịch sử).
        if ($account->transactions()->exists()) {
            return back()->with('error', 'Quỹ đang có giao dịch nên không thể xoá. Hãy chuyển sang "Ngừng hoạt động".');
        }

        $account->delete();

        return redirect()->route('finance.accounts.index')->with('status', 'Đã xoá quỹ tiền.');
    }

    /**
     * FR-M10-05: nạp tiền vào công ty (giao dịch thu, đánh dấu nạp vốn).
     */
    public function deposit(DepositRequest $request, FinanceAccount $account)
    {
        $data = $request->validated();

        $account->transactions()->create([
            'direction' => 'income',
            'amount' => $data['amount'],
            'is_contribution' => true,
            'contributor_name' => $data['contributor_name'] ?? null,
            'occurred_on' => $data['occurred_on'],
            'description' => $data['description'] ?? 'Nạp tiền vào công ty',
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('finance.accounts.index')->with('status', 'Đã nạp tiền vào quỹ "' . $account->name . '".');
    }

    /**
     * FR-M10-04: điều chỉnh số dư — sinh giao dịch chênh lệch (không tính nạp vốn).
     */
    public function adjust(AdjustBalanceRequest $request, FinanceAccount $account)
    {
        $data = $request->validated();
        $diff = round((float) $data['target_balance'] - $account->balance, 2);

        if (abs($diff) < 0.01) {
            return back()->with('status', 'Số dư không thay đổi.');
        }

        $account->transactions()->create([
            'direction' => $diff > 0 ? 'income' : 'expense',
            'amount' => abs($diff),
            'is_contribution' => false,
            'occurred_on' => $data['occurred_on'],
            'description' => $data['description'] ?? 'Điều chỉnh số dư',
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('finance.accounts.index')->with('status', 'Đã điều chỉnh số dư quỹ "' . $account->name . '".');
    }
}
