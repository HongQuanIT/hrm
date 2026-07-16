<?php

namespace App\Http\Requests;

use App\Models\FinanceCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreFinanceTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'account_id' => ['required', 'exists:finance_accounts,id'],
            'direction' => ['required', Rule::in(['income', 'expense'])],
            'amount' => ['required', 'numeric'],
            'occurred_on' => ['required', 'date'],
            'category_id' => ['nullable', 'exists:finance_categories,id'],
            'is_contribution' => ['nullable', 'boolean'],
            'contributor_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'reference' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_contribution' => $this->boolean('is_contribution')]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // BR-M10-19: chỉ giao dịch thu mới được đánh dấu nạp vốn.
            if ($this->boolean('is_contribution') && $this->input('direction') !== 'income') {
                $validator->errors()->add('is_contribution', 'Chỉ giao dịch thu mới được đánh dấu là nạp vốn.');
            }

            // BR-M10-18: chiều của danh mục phải khớp chiều giao dịch.
            if ($this->filled('category_id')) {
                $category = FinanceCategory::find($this->input('category_id'));
                if ($category && $category->direction !== $this->input('direction')) {
                    $validator->errors()->add('category_id', 'Danh mục không cùng chiều thu/chi với giao dịch.');
                }
            }
        });
    }
}
