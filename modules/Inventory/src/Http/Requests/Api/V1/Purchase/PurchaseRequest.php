<?php

namespace DA\Inventory\Http\Requests\Api\V1\Purchase;

use DA\Inventory\DTO\Purchase\PurchaseDTO;
use DA\Inventory\DTO\Purchase\PurchaseOrderItemDTO;
use DA\Inventory\Enums\PurchasePaymentStatus;
use DA\Inventory\Enums\PurchaseStatus;
use DA\Inventory\Services\PurchaseTotals;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

abstract class PurchaseRequest extends FormRequest
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
            'supplier_id' => [
                'required',
                'integer',
                Rule::exists('suppliers', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'store_id' => [
                'required',
                'integer',
                Rule::exists('stores', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'po_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('purchases', 'po_number')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($this->route('id')),
            ],
            'order_date' => ['required', 'date'],
            'currency' => ['required', 'string', 'size:3', 'alpha'],
            'discount_amount' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'tax_amount' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'shipping_amount' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'status' => ['sometimes', Rule::enum(PurchaseStatus::class)],
            'payment_status' => ['sometimes', Rule::enum(PurchasePaymentStatus::class)],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => $this->isMethod('POST')
                ? ['prohibited']
                : [
                    'nullable',
                    'integer',
                    Rule::exists('purchase_order_items', 'id')->where(
                        fn ($query) => $query->where('purchase_order_id', $this->route('id')),
                    ),
                ],
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
            'supplier_id.required' => 'The supplier field is required.',
            'supplier_id.exists' => 'The selected supplier is invalid.',
            'store_id.required' => 'The store field is required.',
            'store_id.exists' => 'The selected store is invalid.',
            'po_number.unique' => 'The PO number has already been taken.',
            'items.required' => 'At least one purchase item is required.',
            'items.min' => 'At least one purchase item is required.',
            'items.*.product_variant_id.exists' => 'The selected product variant is invalid.',
            'items.*.discount_amount' => 'The discount cannot exceed the line amount.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $poNumber = $this->input('po_number');

        $this->merge([
            'po_number' => is_string($poNumber) ? (trim($poNumber) === '' ? null : trim($poNumber)) : $poNumber,
            'currency' => is_string($this->input('currency')) ? strtoupper(trim($this->input('currency'))) : $this->input('currency'),
            'notes' => is_string($this->input('notes')) ? (trim($this->input('notes')) === '' ? null : trim($this->input('notes'))) : $this->input('notes'),
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
                    app(PurchaseTotals::class)->calculate($this->toDTO());
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

    public function toDTO(): PurchaseDTO
    {
        $validated = $this->validated();

        return new PurchaseDTO(
            supplierId: (int) $validated['supplier_id'],
            storeId: (int) $validated['store_id'],
            poNumber: $validated['po_number'] ?? null,
            orderDate: $validated['order_date'],
            currency: $validated['currency'],
            discountAmount: $this->money($validated['discount_amount'] ?? 0),
            taxAmount: $this->money($validated['tax_amount'] ?? 0),
            shippingAmount: $this->money($validated['shipping_amount'] ?? 0),
            status: isset($validated['status'])
                ? PurchaseStatus::from($validated['status'])
                : PurchaseStatus::Pending,
            paymentStatus: isset($validated['payment_status'])
                ? PurchasePaymentStatus::from($validated['payment_status'])
                : PurchasePaymentStatus::Unpaid,
            notes: $validated['notes'] ?? null,
            items: array_map(
                fn (array $item): PurchaseOrderItemDTO => new PurchaseOrderItemDTO(
                    id: isset($item['id']) ? (int) $item['id'] : null,
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
}
