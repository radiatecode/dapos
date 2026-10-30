<?php

namespace DA\Inventory\Http\Requests\Api\V1\Report;

use DA\Inventory\DTO\Report\PurchaseReportFilterDTO;
use DA\Inventory\Enums\PurchasePaymentStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class PurchaseReportRequest extends FormRequest
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
            'supplier_id' => ['nullable', 'integer', 'min:1'],
            'payment_status' => ['nullable', Rule::enum(PurchasePaymentStatus::class)],
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_status.enum' => 'The selected payment status is invalid.',
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

    public function toDTO(): PurchaseReportFilterDTO
    {
        $validated = $this->validated();
        $search = isset($validated['search']) ? trim($validated['search']) : null;

        return new PurchaseReportFilterDTO(
            dateFrom: isset($validated['date_from']) ? Carbon::parse($validated['date_from'])->toDateString() : null,
            dateTo: isset($validated['date_to']) ? Carbon::parse($validated['date_to'])->toDateString() : null,
            storeId: isset($validated['store_id']) ? (int) $validated['store_id'] : null,
            supplierId: isset($validated['supplier_id']) ? (int) $validated['supplier_id'] : null,
            paymentStatus: isset($validated['payment_status'])
                ? PurchasePaymentStatus::from($validated['payment_status'])
                : null,
            search: $search === '' ? null : $search,
        );
    }
}
