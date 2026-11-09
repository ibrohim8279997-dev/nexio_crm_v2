<?php
namespace App\Http\Requests;

use App\Support\ApiException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shape validation for POST /quotes. Authorization (permission + tenant ownership of every referenced record)
 * stays in the controller/policy: this class only guarantees the payload is well-formed, and reports
 * failures in the API's standard error envelope instead of Laravel's default 422 body.
 */
class StoreQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'org_id' => ['nullable', 'string', 'max:40'],
            'contact_id' => ['nullable', 'string', 'max:40'],
            'deal_id' => ['nullable', 'string', 'max:40'],
            'price_list_id' => ['nullable', 'string', 'max:40'],
            'valid_until' => ['nullable', 'date_format:Y-m-d'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.product_id' => ['nullable', 'string', 'max:40'],
            'items.*.description' => ['nullable', 'string', 'max:300'],
            'items.*.quantity' => ['nullable', 'numeric', 'gt:0', 'max:1000000'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ApiException(422, $validator->errors()->first(), 'validation_failed', $validator->errors()->toArray());
    }
}
