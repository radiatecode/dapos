<?php

namespace DA\Inventory\Http\Requests\Api\V1\Report;

use DA\Inventory\DTO\Report\CurrentStockValueReportFilterDTO;
use DA\Inventory\Enums\CurrentStockAvailability;
use DA\Inventory\Enums\CurrentStockValueReportSortColumn;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CurrentStockValueReportRequest extends FormRequest
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
            'store_id' => ['nullable', 'integer', 'min:1'],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'brand_id' => ['nullable', 'integer', 'min:1'],
            'availability' => ['nullable', Rule::enum(CurrentStockAvailability::class)],
            'search' => ['nullable', 'string', 'max:100'],
            'sort_col' => ['nullable', Rule::enum(CurrentStockValueReportSortColumn::class)],
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

    public function toDTO(): CurrentStockValueReportFilterDTO
    {
        $validated = $this->validated();

        return new CurrentStockValueReportFilterDTO(
            storeId: isset($validated['store_id']) ? (int) $validated['store_id'] : null,
            categoryId: isset($validated['category_id']) ? (int) $validated['category_id'] : null,
            brandId: isset($validated['brand_id']) ? (int) $validated['brand_id'] : null,
            availability: isset($validated['availability'])
                ? CurrentStockAvailability::from($validated['availability'])
                : null,
            search: isset($validated['search']) ? trim($validated['search']) : null,
            sortBy: isset($validated['sort_col'])
                ? CurrentStockValueReportSortColumn::from($validated['sort_col'])
                : CurrentStockValueReportSortColumn::StockValue,
            sortDirection: $validated['sort_by'] ?? 'desc',
        );
    }
}
