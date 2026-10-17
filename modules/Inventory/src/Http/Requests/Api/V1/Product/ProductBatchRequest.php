<?php

namespace DA\Inventory\Http\Requests\Api\V1\Product;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductBatchRequest extends FormRequest
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
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
            'store_id' => [
                'required',
                'integer',
                Rule::exists('stores', 'id')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)->where('is_active', true)
                ),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'The spreadsheet file is required.',
            'file.file' => 'The spreadsheet file is required.',
            'file.mimes' => 'The spreadsheet must be an Excel file.',
            'file.max' => 'The spreadsheet may not be greater than 5 MB.',
            'store_id.required' => 'The store field is required.',
            'store_id.integer' => 'The selected store is invalid.',
            'store_id.exists' => 'The selected store is invalid.',
        ];
    }
}
