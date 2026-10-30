<?php

namespace DA\Expense\Http\Resources\Api\V1;

use DA\Expense\Models\Expense;
use DA\Inventory\Http\Resources\Api\V1\StoreResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Expense
 */
class ExpenseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'store_id' => $this->store_id,
            'description' => $this->description,
            'amount' => $this->amount,
            'expense_date' => $this->expense_date?->toDateString(),
            'attachment' => $this->attachment,
            'attachment_url' => is_string($this->attachment) && $this->attachment !== ''
                ? Storage::disk('public')->url($this->attachment)
                : null,
            'status' => $this->status?->value,
            'approval_time' => $this->approval_time,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'category' => ExpenseCategoryResource::make($this->whenLoaded('category')),
            'store' => StoreResource::make($this->whenLoaded('store')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
