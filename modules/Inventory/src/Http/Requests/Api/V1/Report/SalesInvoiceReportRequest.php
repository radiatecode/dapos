<?php

namespace DA\Inventory\Http\Requests\Api\V1\Report;

use DA\Inventory\DTO\Report\SalesInvoiceReportFilterDTO;
use DA\Inventory\Enums\SalePaymentStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class SalesInvoiceReportRequest extends FormRequest
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
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', Rule::when($this->filled('date_from'), ['after_or_equal:date_from'])],
            'store_id' => ['nullable', 'integer', 'min:1'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'payment_status' => ['nullable', Rule::enum(SalePaymentStatus::class)],
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function toDTO(): SalesInvoiceReportFilterDTO
    {
        $validated = $this->validated();

        return new SalesInvoiceReportFilterDTO(
            dateFrom: isset($validated['date_from']) ? Carbon::parse($validated['date_from'])->toDateString() : null,
            dateTo: isset($validated['date_to']) ? Carbon::parse($validated['date_to'])->toDateString() : null,
            storeId: isset($validated['store_id']) ? (int) $validated['store_id'] : null,
            customerId: isset($validated['customer_id']) ? (int) $validated['customer_id'] : null,
            paymentStatus: isset($validated['payment_status'])
                ? SalePaymentStatus::from($validated['payment_status'])
                : null,
            search: isset($validated['search']) ? trim($validated['search']) : null,
        );
    }
}
