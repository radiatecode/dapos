<?php

namespace DA\Inventory\Http\Requests\Api\V1\Purchase;

use DA\Inventory\DTO\Purchase\PurchasePaymentDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePurchasePaymentRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,4'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'The amount field is required.',
            'amount.gt' => 'The amount must be greater than zero.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $reference = $this->input('reference_number');
        $note = $this->input('note');

        $this->merge([
            'reference_number' => is_string($reference) ? (trim($reference) === '' ? null : trim($reference)) : $reference,
            'note' => is_string($note) ? (trim($note) === '' ? null : trim($note)) : $note,
        ]);
    }

    public function toDTO(): PurchasePaymentDTO
    {
        $validated = $this->validated();

        return new PurchasePaymentDTO(
            amount: number_format((float) $validated['amount'], 4, '.', ''),
            referenceNumber: $validated['reference_number'] ?? null,
            note: $validated['note'] ?? null,
        );
    }
}
