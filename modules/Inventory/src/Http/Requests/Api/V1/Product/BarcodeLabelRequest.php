<?php

namespace DA\Inventory\Http\Requests\Api\V1\Product;

use DA\Inventory\DTO\Product\BarcodeLabelFilterDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BarcodeLabelRequest extends FormRequest
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
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? 20);
    }

    public function toDTO(): BarcodeLabelFilterDTO
    {
        $validated = $this->validated();

        return new BarcodeLabelFilterDTO(
            categoryId: isset($validated['category_id']) ? (int) $validated['category_id'] : null,
            search: isset($validated['search']) ? trim($validated['search']) : null,
        );
    }
}
