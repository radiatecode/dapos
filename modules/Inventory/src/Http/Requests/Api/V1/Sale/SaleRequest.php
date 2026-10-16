<?php

namespace DA\Inventory\Http\Requests\Api\V1\Sale;

use DA\Inventory\DTO\Sale\SaleDTO;
use DA\Inventory\DTO\Sale\SaleItemDTO;
use DA\Inventory\DTO\Sale\SalePaymentDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

abstract class SaleRequest extends FormRequest
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
            'store_id' => [
                'required',
                'integer',
            ],
            'customer_id' => [
                'nullable',
                'integer',
            ],
            'order_source' => ['required', 'in:1,2'], // !: pos, 2: mobile
            'sale_date' => ['required', 'date'],
            'discount_type' => ['nullable', 'numeric', 'in:1,2'],
            'discount_amount' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'tax_amount' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'subtotal' => ['required', 'numeric', 'min:0', 'decimal:0,4'],
            'total' => ['required', 'numeric', 'min:0', 'decimal:0,4'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'integer',
            ],
            'items.*.product_variant_id' => [
                'required',
                'integer',
            ],
            'items.*.name' => [
                'required',
                'string',
            ],
            'items.*.variation_name' => [
                'nullable',
                'string',
            ],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,4'],
            'items.*.unitprice' => ['required', 'numeric', 'min:0', 'decimal:0,4'],
            'items.*.item_tax_type' => ['nullable', 'numeric', 'in:1,2'],
            'items.*.taxamount' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'items.*.subtotal' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'items.*.total' => ['required', 'numeric', 'min:0', 'decimal:0,4'],
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.payment_method_id' => [
                'required',
                'integer',
            ],
            'payments.*.received_amount' => ['required', 'numeric', 'min:0', 'decimal:0,4'],
            'payments.*.order_amount' => ['required', 'numeric', 'min:0', 'decimal:0,4'],
            'payments.*.due_amount' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'payments.*.returned_amount' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'store_id.required' => 'The store is required.',
            'items.required' => 'At least one sale item is required.',
            'items.min' => 'At least one sale item is required.',
            'payments.required' => 'At least one payment is required.',
            'payments.min' => 'At least one payment is required.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $payments = $this->input('payments', []);

        $this->merge([
            'sale_date' => $this->filled('sale_date') ? $this->input('sale_date') : now()->toDateString(),
            'payments' => is_array($payments) ? $payments : $this->input('payments'),
        ]);
    }

    public function toDTO(): SaleDTO
    {
        $validated = $this->validated();

        return new SaleDTO(
            storeId: (int) $validated['store_id'],
            customerId: isset($validated['customer_id']) ? (int) $validated['customer_id'] : null,
            saleDate: $validated['sale_date'],
            discountAmount: $validated['discount_amount'] ?? 0,
            discountType: $validated['discount_type'],
            taxAmount: $validated['tax_amount'] ?? 0,
            total: $validated['total'] ?? 0,
            subtotal: $validated['subtotal'] ?? 0,
            orderSource: $validated['order_source'],
            items: array_map(
                fn (array $item): SaleItemDTO => new SaleItemDTO(
                    productVariantId: (int) $item['product_variant_id'],
                    productId: (int) $item['product_id'],
                    productName: $item['name'],
                    variationName: $item['variation_name'] ?? null,
                    quantity: (float) $item['quantity'],
                    unitPrice: (float) $item['unitprice'],
                    itemTaxType: $item['item_tax_type'],
                    taxAmount: $item['taxamount'] ?? 0,
                    subtotal: $item['subtotal'] ?? 0,
                    total: $item['total'],
                ),
                $validated['items'],
            ),
            payments: array_map(
                fn (array $payment): SalePaymentDTO => new SalePaymentDTO(
                    paymentMethod: (int) $payment['payment_method_id'],
                    receivedAmount: $payment['received_amount'],
                    referenceNumber: $payment['reference_number'] ?? null,
                    orderAmount: $payment['order_amount'],
                    dueAmount: $payment['due_amount'] ?? 0,
                    returnedAmount: $payment['returned_amount'] ?? 0,
                ),
                $validated['payments'],
            ),
        );
    }
}
