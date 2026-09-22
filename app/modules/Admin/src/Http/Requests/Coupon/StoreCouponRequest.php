<?php

namespace DA\Admin\Http\Requests\Coupon;

use DA\Admin\DTO\CouponDTO;
use DA\Admin\Enums\CouponDiscountType;
use DA\Admin\Models\AdminUser;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') instanceof AdminUser;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('coupons', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'discount_type' => ['required', Rule::enum(CouponDiscountType::class)],
            'discount_value' => [
                'required',
                'numeric',
                'min:0',
                Rule::when(
                    $this->input('discount_type') === CouponDiscountType::Percentage->value,
                    ['max:100'],
                ),
            ],
            'currency_id' => ['nullable', 'integer', 'exists:currencies,id', 'required_if:discount_type,'.CouponDiscountType::Fixed->value],
            'max_redemptions' => ['nullable', 'integer', 'min:1'],
            'max_redemptions_per_tenant' => ['nullable', 'integer', 'min:1'],
            'minimum_amount' => ['nullable', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'The coupon code is required.',
            'code.unique' => 'This coupon code is already in use.',
            'name.required' => 'The coupon name is required.',
            'discount_type.required' => 'The discount type is required.',
            'discount_value.required' => 'The discount value is required.',
            'currency_id.required_if' => 'A currency is required for a fixed-amount coupon.',
            'ends_at.after_or_equal' => 'The end date must be on or after the start date.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->ajax() || $this->expectsJson()) {
            throw new HttpResponseException(response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422));
        }

        parent::failedValidation($validator);
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code') && is_string($this->input('code'))) {
            $this->merge([
                'code' => Str::upper($this->string('code')->trim()->toString()),
            ]);
        }
    }

    public function toDTO(): CouponDTO
    {
        $data = $this->validated();
        $type = CouponDiscountType::from($data['discount_type']);

        return new CouponDTO(
            code: $data['code'],
            name: $data['name'],
            description: is_string($data['description'] ?? null) && $data['description'] !== ''
                ? $data['description']
                : null,
            discount_type: $type,
            discount_value: number_format((float) $data['discount_value'], 2, '.', ''),
            currency_id: $type === CouponDiscountType::Fixed ? (int) $data['currency_id'] : null,
            max_redemptions: isset($data['max_redemptions']) ? (int) $data['max_redemptions'] : null,
            max_redemptions_per_tenant: isset($data['max_redemptions_per_tenant'])
                ? (int) $data['max_redemptions_per_tenant']
                : null,
            minimum_amount: isset($data['minimum_amount']) && $data['minimum_amount'] !== ''
                ? number_format((float) $data['minimum_amount'], 2, '.', '')
                : null,
            starts_at: isset($data['starts_at']) && $data['starts_at'] !== ''
                ? Carbon::parse($data['starts_at'])
                : null,
            ends_at: isset($data['ends_at']) && $data['ends_at'] !== ''
                ? Carbon::parse($data['ends_at'])
                : null,
            is_active: array_key_exists('is_active', $data) ? $this->boolean('is_active') : true,
        );
    }
}
