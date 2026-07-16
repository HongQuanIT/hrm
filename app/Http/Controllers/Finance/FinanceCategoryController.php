<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFinanceCategoryRequest;
use App\Models\FinanceCategory;

class FinanceCategoryController extends Controller
{
    public function index()
    {
        $categories = FinanceCategory::withCount('transactions')->orderBy('direction')->orderBy('name')->get();

        return view('finance.categories', compact('categories'));
    }

    public function store(StoreFinanceCategoryRequest $request)
    {
        FinanceCategory::create($request->validated());

        return redirect()->route('finance.categories.index')->with('status', 'Đã thêm danh mục.');
    }

    public function update(StoreFinanceCategoryRequest $request, FinanceCategory $category)
    {
        $category->update($request->validated());

        return redirect()->route('finance.categories.index')->with('status', 'Đã cập nhật danh mục.');
    }

    public function destroy(FinanceCategory $category)
    {
        $category->delete();

        return back()->with('status', 'Đã xoá danh mục.');
    }
}
