<?php

namespace DA\Inventory\Http\Requests\Api\V1\StockTransfer;

use DA\Inventory\DTO\StockTransfer\StockTransferDTO;
use DA\Inventory\Enums\StockTransferStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class StockTransferRequest extends FormRequest
{
    abstract protected function creating(): bool;

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
        $allowed = $this->creating()
            ? [StockTransferStatus::Draft->value, StockTransferStatus::Pending->value, StockTransferStatus::Completed->value]
            : [StockTransferStatus::Draft->value, StockTransferStatus::Pending->value];

        return [
            'product_variant_id' => [
                'required',
                'integer',
                Rule::exists('product_variants', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')),
            ],
            'from_store_id' => [
                'required',
                'integer',
                'different:to_store_id',
                Rule::exists('stores', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)->where('is_active', true)),
            ],
            'to_store_id' => [
                'required',
                'integer',
                'different:from_store_id',
                Rule::exists('stores', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)->where('is_active', true)),
            ],
            'transfer_date' => ['required', 'date'],
            'quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,4'],
            'notes' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in($allowed)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_variant_id.required' => 'The product variant field is required.',
            'product_variant_id.exists' => 'The selected product variant is invalid.',
            'from_store_id.required' => 'The source store field is required.',
            'from_store_id.exists' => 'The selected source store is invalid.',
            'from_store_id.different' => 'The destination store must be different from the source store.',
            'to_store_id.required' => 'The destination store field is required.',
            'to_store_id.exists' => 'The selected destination store is invalid.',
            'to_store_id.different' => 'The destination store must be different from the source store.',
            'transfer_date.required' => 'The transfer date field is required.',
            'quantity.required' => 'The quantity field is required.',
            'quantity.gt' => 'The quantity must be greater than zero.',
            'status.in' => 'The selected status is invalid.',
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('from_store_id') !== null && $this->input('from_store_id') === $this->input('to_store_id')) {
                    $validator->errors()->add('to_store_id', 'The destination store must be different from the source store.');
                }
            },
        ];
    }

    public function toDTO(): StockTransferDTO
    {
        $data = $this->validated();

        return new StockTransferDTO(
            productVariantId: (int) $data['product_variant_id'],
            fromStoreId: (int) $data['from_store_id'],
            toStoreId: (int) $data['to_store_id'],
            transferDate: $data['transfer_date'],
            quantity: number_format((float) $data['quantity'], 4, '.', ''),
            notes: $data['notes'] ?? null,
            status: isset($data['status'])
                ? StockTransferStatus::from($data['status'])
                : StockTransferStatus::Draft,
        );
    }
}
