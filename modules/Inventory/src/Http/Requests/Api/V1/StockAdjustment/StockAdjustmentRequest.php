<?php

namespace DA\Inventory\Http\Requests\Api\V1\StockAdjustment;

use DA\Inventory\DTO\StockAdjustment\StockAdjustmentDTO;
use DA\Inventory\Enums\StockAdjustmentStatus;
use DA\Inventory\Enums\StockAdjustmentType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class StockAdjustmentRequest extends FormRequest
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

        return [
            'store_id' => [
                'required',
                'integer',
                Rule::exists('stores', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)->where('is_active', true)),
            ],
            'product_variant_id' => [
                'required',
                'integer',
                Rule::exists('product_variants', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')),
            ],
            'adjustment_date' => ['required', 'date'],
            'adjustment_type' => ['required', Rule::enum(StockAdjustmentType::class)],
            'reason' => ['required', 'string', 'max:255'],
            'adjusted_quantity' => ['required', 'numeric', 'min:0', 'decimal:0,4'],
            'unit_cost' => ['nullable', 'numeric', 'min:0', 'decimal:0,4'],
            'notes' => ['nullable', 'string'],
            'status' => [
                'sometimes',
                Rule::enum(StockAdjustmentStatus::class),
                Rule::notIn([StockAdjustmentStatus::Cancelled->value]),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'store_id.required' => 'The store field is required.',
            'store_id.exists' => 'The selected store is invalid.',
            'product_variant_id.required' => 'The product variant field is required.',
            'product_variant_id.exists' => 'The selected product variant is invalid.',
            'adjustment_date.required' => 'The adjustment date field is required.',
            'adjustment_type.required' => 'The adjustment type field is required.',
            'reason.required' => 'The reason field is required.',
            'adjusted_quantity.required' => 'The adjusted quantity field is required.',
            'unit_cost.required' => 'The unit cost is required when adding stock.',
            'status.not_in' => 'The selected status is invalid.',
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if ($this->input('adjustment_type') !== StockAdjustmentType::Addition->value) {
                    return;
                }

                $unitCost = $this->input('unit_cost');

                if ($unitCost === null || $unitCost === '') {
                    $validator->errors()->add('unit_cost', 'The unit cost is required when adding stock.');
                }
            },
        ];
    }

    public function toDTO(): StockAdjustmentDTO
    {
        $data = $this->validated();
        $status = isset($data['status'])
            ? StockAdjustmentStatus::from($data['status'])
            : StockAdjustmentStatus::Draft;

        if (! $this->creating() && $status === StockAdjustmentStatus::Completed) {
            $status = StockAdjustmentStatus::Draft;
        }

        return new StockAdjustmentDTO(
            storeId: (int) $data['store_id'],
            productVariantId: (int) $data['product_variant_id'],
            adjustmentDate: $data['adjustment_date'],
            adjustmentType: StockAdjustmentType::from($data['adjustment_type']),
            reason: $data['reason'],
            adjustedQuantity: number_format((float) $data['adjusted_quantity'], 4, '.', ''),
            unitCost: isset($data['unit_cost']) && $data['unit_cost'] !== ''
                ? number_format((float) $data['unit_cost'], 4, '.', '')
                : null,
            notes: $data['notes'] ?? null,
            status: $status,
        );
    }
}
