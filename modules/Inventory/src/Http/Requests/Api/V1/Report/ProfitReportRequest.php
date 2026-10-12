<?php

namespace DA\Inventory\Http\Requests\Api\V1\Report;

use DA\Inventory\DTO\Report\ProfitReportFilterDTO;
use DA\Inventory\Enums\ProfitReportSortColumn;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ProfitReportRequest extends FormRequest
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
            'brand_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:100'],
            'sort_col' => ['nullable', Rule::enum(ProfitReportSortColumn::class)],
            'sort_by' => ['nullable', Rule::in(['asc', 'desc'])],
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

    public function toDTO(): ProfitReportFilterDTO
    {
        $validated = $this->validated();

        return new ProfitReportFilterDTO(
            dateFrom: isset($validated['date_from']) ? Carbon::parse($validated['date_from'])->toDateString() : null,
            dateTo: isset($validated['date_to']) ? Carbon::parse($validated['date_to'])->toDateString() : null,
            storeId: isset($validated['store_id']) ? (int) $validated['store_id'] : null,
            categoryId: isset($validated['category_id']) ? (int) $validated['category_id'] : null,
            brandId: isset($validated['brand_id']) ? (int) $validated['brand_id'] : null,
            search: isset($validated['search']) ? trim($validated['search']) : null,
            sortBy: isset($validated['sort_col'])
                ? ProfitReportSortColumn::from($validated['sort_col'])
                : ProfitReportSortColumn::GrossProfit,
            sortDirection: $validated['sort_by'] ?? 'desc',
        );
    }
}
