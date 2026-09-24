<?php

namespace DA\Admin\Http\Requests\Invoice;

use DA\Admin\DTO\GenerateInvoiceDTO;
use DA\Admin\Models\AdminUser;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreInvoiceRequest extends FormRequest
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
            'subscription_id' => ['required', 'integer', 'exists:subscriptions,id'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subscription_id.required' => 'The subscription is required.',
            'subscription_id.exists' => 'The selected subscription is invalid.',
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

    public function toDTO(): GenerateInvoiceDTO
    {
        $data = $this->validated();
        $code = is_string($data['coupon_code'] ?? null) ? trim($data['coupon_code']) : null;

        return new GenerateInvoiceDTO(
            subscription_id: (int) $data['subscription_id'],
            coupon_code: $code !== '' ? $code : null,
        );
    }
}
