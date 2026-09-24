<?php

namespace DA\Inventory\Http\Requests\Api\V1\Product;

use DA\Inventory\DTO\Product\ProductAttributeDTO;
use DA\Inventory\DTO\Product\ProductDTO;
use DA\Inventory\DTO\Product\ProductVariantDTO;
use DA\Inventory\DTO\Product\VariantAttributeValueDTO;
use DA\Inventory\Enums\BarcodeType;
use DA\Inventory\Enums\ProductType;
use DA\Inventory\Models\AttributeValue;
use DA\Inventory\Models\ProductVariant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class ProductRequest extends FormRequest
{
    abstract protected function existingProductId(): ?int;

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
        $isSimple = $this->input('product_type') === ProductType::Simple->value;
        $isVariable = $this->input('product_type') === ProductType::Variable->value;
        $tracksInventory = $this->boolean('track_inventory');

        return [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'brand_id' => [
                'required',
                'integer',
                Rule::exists('brands', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'unit_id' => [
                'required',
                'integer',
                Rule::exists('units', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('products', 'slug')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($this->existingProductId()),
            ],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'product_type' => ['required', Rule::enum(ProductType::class)],
            'track_inventory' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'is_stock_out' => ['sometimes', 'boolean'],
            'manufacture_date' => ['nullable', 'date'],
            'expire_date' => ['nullable', 'date', 'after_or_equal:manufacture_date'],
            'warranty_in_days' => ['nullable', 'integer', 'min:0'],
            'guarantee_in_days' => ['nullable', 'integer', 'min:0'],
            'attributes' => [
                Rule::prohibitedIf($isSimple),
                Rule::requiredIf($isVariable),
                'array',
                Rule::when($isVariable, ['min:1']),
            ],
            'attributes.*.attribute_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('attributes', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'attributes.*.sort_order' => ['sometimes', 'integer', 'min:0'],
            'attributes.*.is_required' => ['sometimes', 'boolean'],
            'variants' => [
                'required',
                'array',
                Rule::when($isSimple, ['size:1'], ['min:1']),
            ],
            'variants.*.id' => $this->existingProductId() === null
                ? ['prohibited']
                : [
                    'nullable',
                    'integer',
                    'distinct',
                    Rule::exists('product_variants', 'id')->where(function ($query) use ($tenantId): void {
                        $query->where('tenant_id', $tenantId)
                            ->where('product_id', $this->existingProductId())
                            ->whereNull('deleted_at');
                    }),
                ],
            'variants.*.sku' => ['required', 'string', 'max:100', 'distinct:ignore_case'],
            'variants.*.barcode' => ['required', 'string', 'max:100', 'distinct:ignore_case'],
            'variants.*.barcode_type' => ['required', Rule::enum(BarcodeType::class)],
            'variants.*.name' => ['nullable', 'string', 'max:200'],
            'variants.*.cost_price' => ['nullable', 'numeric', 'min:0', 'decimal:0,4'],
            'variants.*.selling_price' => ['nullable', 'numeric', 'min:0', 'decimal:0,4'],
            'variants.*.compare_at_price' => ['nullable', 'numeric', 'min:0', 'decimal:0,4'],
            'variants.*.weight' => ['nullable', 'numeric', 'min:0', 'decimal:0,4'],
            'variants.*.quantity' => [
                Rule::prohibitedIf(! $tracksInventory),
                Rule::requiredIf($tracksInventory),
                'numeric',
                'min:0',
                'decimal:0,4',
            ],
            'variants.*.min_stock_level' => [
                Rule::prohibitedIf(! $tracksInventory),
                'nullable',
                'integer',
                'min:0',
            ],
            'variants.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'variants.*.is_default' => ['sometimes', 'boolean'],
            'variants.*.is_active' => ['sometimes', 'boolean'],
            'variants.*.attribute_values' => [
                Rule::prohibitedIf($isSimple),
                Rule::requiredIf($isVariable),
                'array',
                Rule::when($isVariable, ['min:1']),
            ],
            'variants.*.attribute_values.*.attribute_id' => ['required', 'integer'],
            'variants.*.attribute_values.*.attribute_value_id' => ['required', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'slug.unique' => 'The slug has already been taken.',
            'category_id.required' => 'The category field is required.',
            'category_id.exists' => 'The selected category is invalid.',
            'brand_id.required' => 'The brand field is required.',
            'brand_id.exists' => 'The selected brand is invalid.',
            'unit_id.required' => 'The unit field is required.',
            'unit_id.exists' => 'The selected unit is invalid.',
            'product_type.required' => 'The product type field is required.',
            'attributes.required' => 'A variable product must include attributes.',
            'attributes.prohibited' => 'A simple product cannot include attributes.',
            'attributes.min' => 'A variable product must include attributes.',
            'variants.required' => 'At least one variant is required.',
            'variants.size' => 'A simple product must have exactly one variant.',
            'variants.min' => 'A variable product must have at least one variant.',
            'variants.*.id.prohibited' => 'A new product cannot include variant ids.',
            'variants.*.id.exists' => 'The selected variant is invalid.',
            'variants.*.sku.required' => 'The SKU field is required.',
            'variants.*.sku.distinct' => 'The SKU must be unique among variants.',
            'variants.*.barcode.required' => 'The barcode field is required.',
            'variants.*.barcode.distinct' => 'The barcode must be unique among variants.',
            'variants.*.barcode_type.required' => 'The barcode type field is required.',
            'variants.*.quantity.prohibited' => 'Quantity is only allowed when inventory tracking is enabled.',
            'variants.*.quantity.required' => 'The quantity field is required when inventory tracking is enabled.',
            'variants.*.min_stock_level.prohibited' => 'Minimum stock level is only allowed when inventory tracking is enabled.',
            'variants.*.attribute_values.prohibited' => 'A simple product variant cannot include attribute values.',
            'variants.*.attribute_values.required' => 'Each variant must include attribute values.',
            'variants.*.attribute_values.min' => 'Each variant must include attribute values.',
            'expire_date.after_or_equal' => 'The expire date must be a date after or equal to the manufacture date.',
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

                $this->validateVariantCodes($validator);

                if ($this->input('product_type') !== ProductType::Variable->value) {
                    return;
                }

                $this->validateVariableVariants($validator);
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if (! $this->filled('slug') && is_string($this->input('name')) && trim($this->input('name')) !== '') {
            $merge['slug'] = Str::slug($this->input('name'));
        }

        $merge['track_inventory'] = $this->has('track_inventory')
            ? $this->boolean('track_inventory')
            : true;
        $merge['is_active'] = $this->has('is_active')
            ? $this->boolean('is_active')
            : true;
        $merge['is_stock_out'] = $this->has('is_stock_out')
            ? $this->boolean('is_stock_out')
            : false;

        foreach (['manufacture_date', 'expire_date'] as $field) {
            if ($this->input($field) === '') {
                $merge[$field] = null;
            }
        }

        if (is_array($this->input('attributes'))) {
            $merge['attributes'] = $this->normalizedAttributes();
        }

        if (is_array($this->input('variants'))) {
            $merge['variants'] = $this->normalizedVariants();
        }

        $this->merge($merge);
    }

    public function toDTO(): ProductDTO
    {
        $data = $this->validated();
        $image = $this->file('image');

        return new ProductDTO(
            categoryId: (int) $data['category_id'],
            brandId: (int) $data['brand_id'],
            unitId: (int) $data['unit_id'],
            name: $data['name'],
            slug: isset($data['slug']) && $data['slug'] !== '' ? $data['slug'] : null,
            description: $data['description'] ?? null,
            image: $image instanceof UploadedFile ? $image : null,
            productType: ProductType::from($data['product_type']),
            trackInventory: $data['track_inventory'] ?? true,
            isActive: $data['is_active'] ?? true,
            isStockOut: $data['is_stock_out'] ?? false,
            manufactureDate: $data['manufacture_date'] ?? null,
            expireDate: $data['expire_date'] ?? null,
            warrantyInDays: isset($data['warranty_in_days']) ? (int) $data['warranty_in_days'] : null,
            guaranteeInDays: isset($data['guarantee_in_days']) ? (int) $data['guarantee_in_days'] : null,
            attributes: $this->attributeDTOs($data['attributes'] ?? []),
            variants: $this->variantDTOs($data['variants']),
        );
    }

    private function validateVariantCodes(Validator $validator): void
    {
        /** @var list<array<string, mixed>> $variants */
        $variants = $this->input('variants', []);
        $skus = array_column($variants, 'sku');
        $barcodes = array_column($variants, 'barcode');
        $productId = $this->existingProductId();

        $takenSkus = ProductVariant::queries()->takenSkus($skus, $productId)
            ->map(fn (mixed $sku): string => Str::lower((string) $sku));
        $takenBarcodes = ProductVariant::queries()->takenBarcodes($barcodes, $productId)
            ->map(fn (mixed $barcode): string => Str::lower((string) $barcode));

        foreach ($variants as $index => $variant) {
            if ($takenSkus->contains(Str::lower((string) $variant['sku']))) {
                $validator->errors()->add("variants.$index.sku", 'The SKU has already been taken.');
            }

            if ($takenBarcodes->contains(Str::lower((string) $variant['barcode']))) {
                $validator->errors()->add("variants.$index.barcode", 'The barcode has already been taken.');
            }
        }
    }

    private function validateVariableVariants(Validator $validator): void
    {
        /** @var list<array<string, mixed>> $attributes */
        $attributes = $this->input('attributes', []);
        /** @var list<array<string, mixed>> $variants */
        $variants = $this->input('variants', []);

        $assignedAttributeIds = collect($attributes)
            ->pluck('attribute_id')
            ->map(fn (mixed $id): int => (int) $id);

        $requiredAttributeIds = collect($attributes)
            ->filter(fn (array $attribute): bool => (bool) ($attribute['is_required'] ?? true))
            ->pluck('attribute_id')
            ->map(fn (mixed $id): int => (int) $id);

        $defaultCount = collect($variants)
            ->filter(fn (array $variant): bool => (bool) ($variant['is_default'] ?? false))
            ->count();

        if ($defaultCount > 1) {
            $validator->errors()->add('variants', 'Only one variant can be the default.');
        }

        $valueIds = collect($variants)
            ->flatMap(fn (array $variant): array => array_column($variant['attribute_values'] ?? [], 'attribute_value_id'))
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        $attributeValues = AttributeValue::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->whereIn('id', $valueIds)
            ->get()
            ->keyBy('id');

        $combinations = [];

        foreach ($variants as $index => $variant) {
            $selectedAttributeIds = [];
            $pairs = [];

            foreach ($variant['attribute_values'] as $valueIndex => $selection) {
                $attributeId = (int) $selection['attribute_id'];
                $attributeValueId = (int) $selection['attribute_value_id'];

                if (in_array($attributeId, $selectedAttributeIds, true)) {
                    $validator->errors()->add(
                        "variants.$index.attribute_values.$valueIndex.attribute_id",
                        'Each attribute can only be selected once per variant.',
                    );
                }

                $selectedAttributeIds[] = $attributeId;

                if (! $assignedAttributeIds->contains($attributeId)) {
                    $validator->errors()->add(
                        "variants.$index.attribute_values.$valueIndex.attribute_id",
                        'The selected attribute is not assigned to this product.',
                    );
                }

                $attributeValue = $attributeValues->get($attributeValueId);

                if ($attributeValue === null || (int) $attributeValue->attribute_id !== $attributeId) {
                    $validator->errors()->add(
                        "variants.$index.attribute_values.$valueIndex.attribute_value_id",
                        'The selected attribute value is invalid.',
                    );
                }

                $pairs[] = $attributeId.':'.$attributeValueId;
            }

            $missing = $requiredAttributeIds->diff($selectedAttributeIds);

            if ($missing->isNotEmpty()) {
                $validator->errors()->add(
                    "variants.$index.attribute_values",
                    'Each variant must include every required attribute.',
                );
            }

            sort($pairs);
            $signature = implode('|', $pairs);

            if (isset($combinations[$signature])) {
                $validator->errors()->add(
                    "variants.$index.attribute_values",
                    'Each variant must use a different combination of attribute values.',
                );
            }

            $combinations[$signature] = true;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizedAttributes(): array
    {
        $attributes = [];

        foreach ($this->input('attributes') as $attribute) {
            if (! is_array($attribute)) {
                $attributes[] = $attribute;

                continue;
            }

            if (array_key_exists('is_required', $attribute)) {
                $attribute['is_required'] = filter_var($attribute['is_required'], FILTER_VALIDATE_BOOLEAN);
            }

            $attributes[] = $attribute;
        }

        return $attributes;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizedVariants(): array
    {
        $variants = [];

        foreach ($this->input('variants') as $variant) {
            if (! is_array($variant)) {
                $variants[] = $variant;

                continue;
            }

            foreach (['sku', 'barcode', 'name'] as $field) {
                if (isset($variant[$field]) && is_string($variant[$field])) {
                    $variant[$field] = trim($variant[$field]);
                }
            }

            foreach (['is_default', 'is_active'] as $field) {
                if (array_key_exists($field, $variant)) {
                    $variant[$field] = filter_var($variant[$field], FILTER_VALIDATE_BOOLEAN);
                }
            }

            $variants[] = $variant;
        }

        return $variants;
    }

    /**
     * @param  list<array<string, mixed>>  $attributes
     * @return list<ProductAttributeDTO>
     */
    private function attributeDTOs(array $attributes): array
    {
        return array_map(fn (array $attribute): ProductAttributeDTO => new ProductAttributeDTO(
            attributeId: (int) $attribute['attribute_id'],
            sortOrder: (int) ($attribute['sort_order'] ?? 0),
            isRequired: (bool) ($attribute['is_required'] ?? true),
        ), $attributes);
    }

    /**
     * @param  list<array<string, mixed>>  $variants
     * @return list<ProductVariantDTO>
     */
    private function variantDTOs(array $variants): array
    {
        return array_map(function (array $variant, int $index): ProductVariantDTO {
            $image = $this->file("variants.$index.image");

            return new ProductVariantDTO(
                id: isset($variant['id']) ? (int) $variant['id'] : null,
                sku: $variant['sku'],
                barcode: $variant['barcode'],
                barcodeType: BarcodeType::from($variant['barcode_type']),
                name: $variant['name'] ?? null,
                costPrice: $this->decimal($variant['cost_price'] ?? null),
                sellingPrice: $this->decimal($variant['selling_price'] ?? null),
                compareAtPrice: $this->decimal($variant['compare_at_price'] ?? null),
                weight: $this->decimal($variant['weight'] ?? null),
                quantity: array_key_exists('quantity', $variant) ? $this->decimal($variant['quantity']) : null,
                minStockLevel: isset($variant['min_stock_level']) ? (int) $variant['min_stock_level'] : null,
                image: $image instanceof UploadedFile ? $image : null,
                isDefault: (bool) ($variant['is_default'] ?? false),
                isActive: (bool) ($variant['is_active'] ?? true),
                attributeValues: $this->attributeValueDTOs($variant['attribute_values'] ?? []),
            );
        }, $variants, array_keys($variants));
    }

    /**
     * @param  list<array<string, mixed>>  $attributeValues
     * @return list<VariantAttributeValueDTO>
     */
    private function attributeValueDTOs(array $attributeValues): array
    {
        return array_map(fn (array $attributeValue): VariantAttributeValueDTO => new VariantAttributeValueDTO(
            attributeId: (int) $attributeValue['attribute_id'],
            attributeValueId: (int) $attributeValue['attribute_value_id'],
        ), $attributeValues);
    }

    private function decimal(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return number_format((float) $value, 4, '.', '');
    }
}
