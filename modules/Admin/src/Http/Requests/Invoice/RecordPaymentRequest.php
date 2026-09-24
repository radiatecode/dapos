<?php

namespace DA\Admin\Http\Requests\Invoice;

use DA\Admin\DTO\RecordPaymentDTO;
use DA\Admin\Enums\PaymentMethod;
use DA\Admin\Models\AdminUser;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class RecordPaymentRequest extends FormRequest
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
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'transaction_id' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_method.required' => 'The payment method is required.',
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

    public function toDTO(): RecordPaymentDTO
    {
        $data = $this->validated();
        $transactionId = is_string($data['transaction_id'] ?? null) ? trim($data['transaction_id']) : null;

        return new RecordPaymentDTO(
            payment_method: PaymentMethod::from($data['payment_method']),
            transaction_id: $transactionId !== '' ? $transactionId : null,
        );
    }
}
