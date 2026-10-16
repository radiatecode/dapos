<?php

namespace DA\Inventory\Http\Requests\Api\V1\Sale;

use DA\Inventory\DTO\Sale\SalePaymentDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSalePaymentRequest extends FormRequest
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
            'payment_method_id' => [
                'required',
                'integer',
            ],
            'order_amount' => ['required', 'numeric', 'min:0', 'decimal:0,4'],
            'received_amount' => ['required', 'numeric', 'gt:0', 'decimal:0,4'],
            'due_amount' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'returned_amount' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_method_id.required' => 'The payment method field is required.',
            'received_amount.gt' => 'The received amount must be greater than zero.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $reference = $this->input('reference_number');

        $this->merge([
            'reference_number' => is_string($reference)
                ? (trim($reference) === '' ? null : trim($reference))
                : $reference,
        ]);
    }

    public function toDTO(): SalePaymentDTO
    {
        $validated = $this->validated();

        return new SalePaymentDTO(
            paymentMethod: (int) $validated['payment_method'],
            receivedAmount: $validated['received_amount'],
            referenceNumber: $validated['reference_number'] ?? null,
            orderAmount: $validated['order_amount'],
            dueAmount: $validated['due_amount'] ?? 0,
            returnedAmount: $validated['returned_amount'] ?? 0,
        );
    }
}
