<?php

namespace DA\Admin\Http\Requests\Coupon;

use DA\Admin\Models\Coupon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateCouponRequest extends StoreCouponRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Coupon|null $coupon */
        $coupon = $this->route('coupon');

        return [
            ...parent::rules(),
            'code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('coupons', 'code')->ignore($coupon)],
        ];
    }
}
