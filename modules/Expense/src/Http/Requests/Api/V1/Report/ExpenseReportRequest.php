<?php

namespace DA\Expense\Http\Requests\Api\V1\Report;

use DA\Expense\DTO\Report\ExpenseReportFilterDTO;
use DA\Expense\Enums\ExpenseStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ExpenseReportRequest extends FormRequest
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
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', Rule::when($this->filled('date_from'), ['after_or_equal:date_from'])],
            'store_id' => ['nullable', 'integer', 'min:1'],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::enum(ExpenseStatus::class)],
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.enum' => 'The selected status is invalid.',
            'date_to.after_or_equal' => 'The end date must be on or after the start date.',
        ];
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? 15);
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('per_page') && $this->filled('limit')) {
            $this->merge(['per_page' => $this->input('limit')]);
        }
    }

    public function toDTO(): ExpenseReportFilterDTO
    {
        $validated = $this->validated();
        $search = isset($validated['search']) ? trim($validated['search']) : null;

        return new ExpenseReportFilterDTO(
            dateFrom: isset($validated['date_from']) ? Carbon::parse($validated['date_from'])->toDateString() : null,
            dateTo: isset($validated['date_to']) ? Carbon::parse($validated['date_to'])->toDateString() : null,
            storeId: isset($validated['store_id']) ? (int) $validated['store_id'] : null,
            categoryId: isset($validated['category_id']) ? (int) $validated['category_id'] : null,
            status: isset($validated['status']) ? ExpenseStatus::from($validated['status']) : null,
            search: $search === '' ? null : $search,
        );
    }
}
