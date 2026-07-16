<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFinanceTransactionRequest;
use App\Models\FinanceAccount;
use App\Models\FinanceCategory;
use App\Models\FinanceTransaction;
use App\Services\FinanceService;
use Illuminate\Http\Request;

class FinanceTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = FinanceTransaction::with(['account', 'category'])->latest('occurred_on')->latest('id');

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->input('account_id'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }
        if (in_array($request->input('direction'), ['income', 'expense'], true)) {
            $query->where('direction', $request->input('direction'));
        }
        if ($request->input('flow') === 'contribution') {
            $query->where('is_contribution', true);
        }
        if ($request->filled('from')) {
            $query->whereDate('occurred_on', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('occurred_on', '<=', $request->date('to'));
        }

        $transactions = $query->paginate(15)->withQueryString();
        $accounts = FinanceAccount::orderBy('name')->get();
        $categories = FinanceCategory::orderBy('name')->get();

        return view('finance.transactions', compact('transactions', 'accounts', 'categories'));
    }

    public function store(StoreFinanceTransactionRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        FinanceTransaction::create($data);

        return redirect()->route('finance.transactions.index')->with('status', 'Đã thêm giao dịch.');
    }

    public function update(StoreFinanceTransactionRequest $request, FinanceTransaction $transaction, FinanceService $finance)
    {
        $transaction->update($request->validated());

        // Nếu giao dịch gắn công nợ, cập nhật lại trạng thái công nợ.
        if ($transaction->debt) {
            $finance->refreshDebtStatus($transaction->debt);
        }

        return redirect()->route('finance.transactions.index')->with('status', 'Đã cập nhật giao dịch.');
    }

    public function destroy(FinanceTransaction $transaction, FinanceService $finance)
    {
        $debt = $transaction->debt;
        $transaction->delete();

        // BR-M10-15: xoá giao dịch gắn nợ phải tính lại số đã trả & trạng thái.
        if ($debt) {
            $finance->refreshDebtStatus($debt);
        }

        return back()->with('status', 'Đã xoá giao dịch.');
    }
}
