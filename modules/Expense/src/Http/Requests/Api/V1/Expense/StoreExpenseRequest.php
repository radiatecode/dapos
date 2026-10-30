<?php

namespace DA\Expense\Http\Requests\Api\V1\Expense;

use DA\Expense\DTO\Expense\ExpenseDTO;
use DA\Expense\Enums\ExpenseStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = auth()->user()->tenant_id;

        return [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('expense_categories', 'id')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)->where('is_active', true),
                ),
            ],
            'store_id' => [
                'required',
                'integer',
                Rule::exists('stores', 'id')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId),
                ),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,4'],
            'expense_date' => ['required', 'date'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
            'status' => ['sometimes', 'nullable', Rule::enum(ExpenseStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'The category id field is required.',
            'category_id.exists' => 'The selected category is invalid.',
            'store_id.required' => 'The store id field is required.',
            'store_id.exists' => 'The selected store is invalid.',
            'amount.required' => 'The amount field is required.',
            'amount.gt' => 'The amount field must be greater than 0.',
            'expense_date.required' => 'The expense date field is required.',
            'attachment.mimes' => 'The attachment must be a file of type: jpg, jpeg, png, webp, pdf.',
            'status.enum' => 'The selected status is invalid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('description') && is_string($this->input('description'))) {
            $description = trim($this->string('description')->toString());
            $merge['description'] = $description === '' ? null : $description;
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function toDTO(): ExpenseDTO
    {
        $data = $this->validated();
        $attachment = $this->file('attachment');

        return new ExpenseDTO(
            categoryId: (int) $data['category_id'],
            storeId: (int) $data['store_id'],
            description: $data['description'] ?? null,
            amount: (string) $data['amount'],
            expenseDate: $data['expense_date'],
            attachment: $attachment instanceof UploadedFile ? $attachment : null,
            status: isset($data['status']) ? ExpenseStatus::from($data['status']) : null,
        );
    }
}
