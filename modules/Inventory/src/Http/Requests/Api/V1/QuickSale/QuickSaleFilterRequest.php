<?php

namespace DA\Inventory\Http\Requests\Api\V1\QuickSale;

use DA\Inventory\DTO\QuickSale\QuickSaleFilterDTO;
use DA\Inventory\Enums\PaymentMethod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class QuickSaleFilterRequest extends FormRequest
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
            'payment_method' => ['nullable', 'integer', Rule::enum(PaymentMethod::class)],
            'search' => ['nullable', 'string', 'max:100'],
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

    public function toDTO(): QuickSaleFilterDTO
    {
        $validated = $this->validated();

        return new QuickSaleFilterDTO(
            dateFrom: isset($validated['date_from']) ? Carbon::parse($validated['date_from'])->toDateString() : null,
            dateTo: isset($validated['date_to']) ? Carbon::parse($validated['date_to'])->toDateString() : null,
            storeId: isset($validated['store_id']) ? (int) $validated['store_id'] : null,
            paymentMethod: isset($validated['payment_method']) ? PaymentMethod::from((int) $validated['payment_method']) : null,
            search: isset($validated['search']) ? trim($validated['search']) : null,
        );
    }
}
