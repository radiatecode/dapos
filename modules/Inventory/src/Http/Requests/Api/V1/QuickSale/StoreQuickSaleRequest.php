<?php

namespace DA\Inventory\Http\Requests\Api\V1\QuickSale;

use DA\Inventory\DTO\QuickSale\QuickSaleDTO;
use DA\Inventory\DTO\QuickSale\QuickSaleItemDTO;
use DA\Inventory\Enums\PaymentMethod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuickSaleRequest extends FormRequest
{
    public const MAX_ITEMS = 200;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = auth()->user()->tenant_id;

        return [
            'store_id' => [
                'required',
                'integer',
                Rule::exists('stores', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)->where('is_active', true)),
            ],
            'sale_date' => ['required', 'date'],
            'payment_method' => ['required', 'integer', Rule::enum(PaymentMethod::class)->except([PaymentMethod::DUE])],
            'received_amount' => ['required', 'numeric', 'gt:0', 'decimal:0,4', 'max:99999999999'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1', 'max:'.self::MAX_ITEMS],
            'items.*.title' => ['nullable', 'string', 'max:150'],
            'items.*.unit_amount' => ['required', 'numeric', 'gt:0', 'decimal:0,4', 'max:9999999999'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,4', 'max:99999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'store_id.required' => 'The store is required.',
            'store_id.exists' => 'The selected store is invalid.',
            'items.required' => 'Add at least one amount before taking payment.',
            'items.min' => 'Add at least one amount before taking payment.',
            'items.max' => 'A quick sale can have at most '.self::MAX_ITEMS.' lines.',
            'payment_method.enum' => 'The selected payment method is invalid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $items = $this->input('items');

        $this->merge([
            'sale_date' => $this->filled('sale_date') ? $this->input('sale_date') : now()->toDateString(),
            'items' => is_array($items)
                ? array_map(
                    fn (mixed $item): mixed => is_array($item) ? [...$item, 'quantity' => $item['quantity'] ?? 1] : $item,
                    $items,
                )
                : $items,
        ]);
    }

    public function toDTO(): QuickSaleDTO
    {
        $validated = $this->validated();

        return new QuickSaleDTO(
            storeId: (int) $validated['store_id'],
            saleDate: $validated['sale_date'],
            paymentMethod: PaymentMethod::from((int) $validated['payment_method']),
            receivedAmount: (string) $validated['received_amount'],
            referenceNumber: isset($validated['reference_number']) ? trim($validated['reference_number']) : null,
            note: isset($validated['note']) ? trim($validated['note']) : null,
            items: array_values(array_map(
                fn (array $item): QuickSaleItemDTO => new QuickSaleItemDTO(
                    title: isset($item['title']) && trim($item['title']) !== '' ? trim($item['title']) : null,
                    unitAmount: (string) $item['unit_amount'],
                    quantity: (string) $item['quantity'],
                ),
                $validated['items'],
            )),
        );
    }
}
