<?php

namespace DA\Inventory\Http\Requests\Api\V1\PurchaseReturn;

use DA\Inventory\Actions\PurchaseReturn\AssertPurchaseReturn;
use DA\Inventory\DTO\PurchaseReturn\PurchaseReturnDTO;
use DA\Inventory\DTO\PurchaseReturn\PurchaseReturnItemDTO;
use DA\Inventory\Enums\PurchaseReturnStatus;
use DA\Inventory\Services\PurchaseReturnTotals;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

abstract class PurchaseReturnRequest extends FormRequest
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
        $tenantId = auth()->user()->tenant_id;

        return [
            'purchase_id' => ['required', 'integer'],
            'return_date' => ['required', 'date'],
            'discount_amount' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'tax_amount' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'status' => ['sometimes', Rule::in([
                PurchaseReturnStatus::Draft->value,
                PurchaseReturnStatus::Pending->value,
            ])],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => $this->isMethod('POST')
                ? ['prohibited']
                : [
                    'nullable',
                    'integer',
                    Rule::exists('purchase_return_items', 'id')->where(
                        fn ($query) => $query->where('purchase_return_id', $this->route('id')),
                    ),
                ],
            'items.*.purchase_order_item_id' => ['required', 'integer'],
            'items.*.product_variant_id' => [
                'required',
                'integer',
                Rule::exists('product_variants', 'id')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at'),
                ),
            ],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,4'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0', 'decimal:0,4'],
            'items.*.discount_amount' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'items.*.tax_amount' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'purchase_id.required' => 'The purchase field is required.',
            'return_date.required' => 'The return date field is required.',
            'items.required' => 'At least one purchase return item is required.',
            'items.min' => 'At least one purchase return item is required.',
            'items.*.product_variant_id.exists' => 'The selected product variant is invalid.',
            'items.*.discount_amount' => 'The discount cannot exceed the line amount.',
            'status.in' => 'The selected status is invalid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason' => $this->blankToNull($this->input('reason')),
            'notes' => $this->blankToNull($this->input('notes')),
        ]);
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                foreach ($this->input('items', []) as $index => $item) {
                    $gross = bcmul($this->money($item['quantity'] ?? 0), $this->money($item['unit_cost'] ?? 0), 4);
                    $discount = $this->money($item['discount_amount'] ?? 0);

                    if (bccomp($discount, $gross, 4) === 1) {
                        $validator->errors()->add(
                            "items.{$index}.discount_amount",
                            'The discount cannot exceed the line amount.',
                        );
                    }
                }

                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                try {
                    app(AssertPurchaseReturn::class)->handle($this->toDTO());
                    app(PurchaseReturnTotals::class)->calculate($this->toDTO());
                } catch (ValidationException $exception) {
                    foreach ($exception->errors() as $field => $messages) {
                        foreach ($messages as $message) {
                            $validator->errors()->add($field, $message);
                        }
                    }
                }
            },
        ];
    }

    public function toDTO(): PurchaseReturnDTO
    {
        $validated = $this->validated();

        return new PurchaseReturnDTO(
            purchaseId: (int) $validated['purchase_id'],
            returnDate: $validated['return_date'],
            discountAmount: $this->money($validated['discount_amount'] ?? 0),
            taxAmount: $this->money($validated['tax_amount'] ?? 0),
            status: isset($validated['status'])
                ? PurchaseReturnStatus::from($validated['status'])
                : PurchaseReturnStatus::Draft,
            reason: $validated['reason'] ?? null,
            notes: $validated['notes'] ?? null,
            items: array_map(
                fn (array $item): PurchaseReturnItemDTO => new PurchaseReturnItemDTO(
                    id: isset($item['id']) ? (int) $item['id'] : null,
                    purchaseOrderItemId: (int) $item['purchase_order_item_id'],
                    productVariantId: (int) $item['product_variant_id'],
                    quantity: $this->money($item['quantity']),
                    unitCost: $this->money($item['unit_cost']),
                    discountAmount: $this->money($item['discount_amount'] ?? 0),
                    taxAmount: $this->money($item['tax_amount'] ?? 0),
                ),
                $validated['items'],
            ),
        );
    }

    private function money(mixed $value): string
    {
        return number_format((float) $value, 4, '.', '');
    }

    private function blankToNull(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
