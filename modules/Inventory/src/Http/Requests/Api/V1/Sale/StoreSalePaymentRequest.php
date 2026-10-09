<?php

namespace DA\Inventory\Http\Requests\Api\V1\Sale;

use DA\Inventory\DTO\Sale\SalePaymentDTO;
use DA\Inventory\Services\Decimal;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
                Rule::exists('payment_methods', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'received_amount' => ['required', 'numeric', 'gt:0', 'decimal:0,4'],
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
            'payment_method_id.exists' => 'The selected payment method is invalid.',
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
            paymentMethodId: (int) $validated['payment_method_id'],
            receivedAmount: Decimal::normalize($validated['received_amount']),
            referenceNumber: $validated['reference_number'] ?? null,
        );
    }
}
